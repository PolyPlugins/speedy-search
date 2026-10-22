<?php

namespace PolyPlugins\Speedy_Search\Backend;

use PolyPlugins\Speedy_Search\Utils;

if (!defined('ABSPATH')) exit;

class Product_Facets {

  const OPTION_KEY    = 'speedy_search_product_facets';
  const SNAPSHOT_META = '_speedy_search_facet_snapshot';

  /**
   * In-request copy of the aggregate store.
   *
   * @var array|null
   */
  private static $store = null;

  /**
   * Cached answer for whether any visible in-stock product exists.
   *
   * @var bool|null
   */
  private static $has_visible_in_stock = null;

  /**
   * Whether the full-catalog filter option is on.
   *
   * @return bool
   */
  public static function is_enabled() {
    return (bool) Utils::get_option('filters_catalog_enabled');
  }

  /**
   * Whether a finished choice list is available.
   *
   * @return bool
   */
  public static function is_ready() {
    if (!self::is_enabled()) {
      return false;
    }

    $store = self::get_store();

    return !empty($store['ready']);
  }

  /**
   * Choice lists for the search UI. Null until a product index has finished.
   *
   * @return array|null
   */
  public static function public_payload() {
    if (!self::is_ready()) {
      return null;
    }

    $store      = self::get_store();
    $categories = array();

    foreach ($store['categories'] as $id => $row) {
      if (empty($row['count'])) {
        continue;
      }

      $categories[] = array(
        'id'   => (int) $id,
        'name' => isset($row['name']) ? (string) $row['name'] : (string) $id,
      );
    }

    usort($categories, function ($a, $b) {
      return strcasecmp($a['name'], $b['name']);
    });

    $attributes = array();

    foreach ($store['attributes'] as $key => $block) {
      if (empty($block['values']) || !is_array($block['values'])) {
        continue;
      }

      $values = array();

      foreach ($block['values'] as $slug => $value) {
        if (empty($value['count'])) {
          continue;
        }

        $values[] = array(
          'slug' => (string) $slug,
          'name' => isset($value['name']) ? (string) $value['name'] : (string) $slug,
        );
      }

      if (empty($values)) {
        continue;
      }

      usort($values, function ($a, $b) {
        return strcasecmp($a['name'], $b['name']);
      });

      $attributes[$key] = array(
        'label'  => isset($block['label']) ? (string) $block['label'] : (string) $key,
        'values' => $values,
      );
    }

    return array(
      'categories'    => $categories,
      'attributes'    => $attributes,
      'custom_fields' => self::public_value_map($store['custom_fields']),
      'acf_fields'    => self::public_value_map($store['acf_fields']),
      'price'         => array(
        'min' => isset($store['price']['min']) ? (float) $store['price']['min'] : 0,
        'max' => isset($store['price']['max']) ? (float) $store['price']['max'] : 0,
      ),
    );
  }

  /**
   * Sanitize catalog filter args from the latest endpoint.
   *
   * @param mixed $raw JSON string or array.
   * @return array
   */
  public static function sanitize_filters($raw) {
    if (is_string($raw) && $raw !== '') {
      $decoded = json_decode(wp_unslash($raw), true);
      $raw     = is_array($decoded) ? $decoded : array();
    }

    if (!is_array($raw)) {
      return array();
    }

    $filters = array();

    if (!empty($raw['category'])) {
      $category = absint($raw['category']);

      if ($category > 0) {
        $filters['category'] = $category;
      }
    }

    if (isset($raw['rating']) && $raw['rating'] !== '') {
      $rating = (float) $raw['rating'];

      if ($rating > 0) {
        $filters['rating'] = $rating;
      }
    }

    if (isset($raw['min_price']) && $raw['min_price'] !== '') {
      $filters['min_price'] = (float) $raw['min_price'];
    }

    if (isset($raw['max_price']) && $raw['max_price'] !== '') {
      $filters['max_price'] = (float) $raw['max_price'];
    }

    if (isset($filters['min_price']) && isset($filters['max_price']) && $filters['min_price'] > $filters['max_price']) {
      $swap                 = $filters['min_price'];
      $filters['min_price'] = $filters['max_price'];
      $filters['max_price'] = $swap;
    }

    $allowed_attributes = array_flip(Utils::get_filters_attributes_allowed_keys());
    $attributes         = array();

    if (!empty($raw['attributes']) && is_array($raw['attributes'])) {
      foreach ($raw['attributes'] as $key => $slug) {
        $key  = sanitize_key($key);
        $slug = sanitize_title($slug);

        if ($key === '' || $slug === '' || !isset($allowed_attributes[$key])) {
          continue;
        }

        $attributes[$key] = $slug;
      }
    }

    if (!empty($attributes)) {
      $filters['attributes'] = $attributes;
    }

    $custom_fields = self::sanitize_field_map(
      isset($raw['custom_fields']) ? $raw['custom_fields'] : array(),
      Utils::get_filters_custom_fields_ordered_keys()
    );

    if (!empty($custom_fields)) {
      $filters['custom_fields'] = $custom_fields;
    }

    $acf_fields = self::sanitize_field_map(
      isset($raw['acf_fields']) ? $raw['acf_fields'] : array(),
      Utils::get_filters_acf_fields_ordered_keys()
    );

    if (!empty($acf_fields)) {
      $filters['acf_fields'] = $acf_fields;
    }

    return $filters;
  }

  /**
   * Product IDs matching the filters, newest first.
   * Featured matches come first when $featured_first is true.
   *
   * @param array $filters
   * @param int   $limit
   * @param bool  $featured_first
   * @param bool  $in_stock
   * @return array
   */
  public static function query_product_ids($filters, $limit, $featured_first, $in_stock = true) {
    $limit   = max(1, (int) $limit);
    $filters = is_array($filters) ? $filters : array();

    if (!$featured_first) {
      return self::query_id_slice($filters, $limit, 'any', $in_stock);
    }

    $featured  = self::query_id_slice($filters, $limit, 'only', $in_stock);
    $remaining = $limit - count($featured);

    if ($remaining < 1) {
      return $featured;
    }

    $rest = self::query_id_slice($filters, $remaining, 'exclude', $in_stock);

    return array_merge($featured, $rest);
  }

  /**
   * True when a published, non-hidden, in-stock product exists.
   *
   * @return bool
   */
  public static function has_visible_in_stock() {
    if (self::$has_visible_in_stock === null) {
      self::$has_visible_in_stock = self::query_has_visible_in_stock();
    }

    return self::$has_visible_in_stock;
  }

  /**
   * Clear the choice list at the start of a product reindex.
   *
   * @return void
   */
  public static function begin_reindex() {
    if (!self::is_enabled()) {
      return;
    }

    global $wpdb;

    $wpdb->query($wpdb->prepare(
      "DELETE FROM %i WHERE meta_key = %s",
      $wpdb->postmeta,
      self::SNAPSHOT_META
    ));

    self::$has_visible_in_stock = null;
    self::$store                = self::empty_store();
    self::persist();
  }

  /**
   * Mark the choice list ready and refresh price bounds.
   *
   * @return void
   */
  public static function finish_reindex() {
    if (!self::is_enabled()) {
      return;
    }

    self::ensure_store();
    self::$store['price'] = self::query_price_bounds();
    self::$store['ready'] = true;
    self::persist();
  }

  /**
   * Rebuild one product's contribution from its current data.
   *
   * @param int  $post_id
   * @param bool $refresh_price
   * @return void
   */
  public static function sync_product($post_id, $refresh_price = true) {
    if (!self::is_enabled()) {
      return;
    }

    $post_id = (int) $post_id;

    if ($post_id < 1) {
      return;
    }

    self::apply_snapshot($post_id, self::build_snapshot($post_id));

    if ($refresh_price && self::is_ready()) {
      self::ensure_store();
      self::$store['price'] = self::query_price_bounds();
    }

    self::persist();
  }

  /**
   * Drop a product from the choice list without reading it back.
   *
   * @param int $post_id
   * @return void
   */
  public static function forget_product($post_id) {
    if (!self::is_enabled()) {
      return;
    }

    $post_id = (int) $post_id;

    if ($post_id < 1) {
      return;
    }

    self::apply_snapshot($post_id, array());

    if (self::is_ready()) {
      self::ensure_store();
      self::$store['price'] = self::query_price_bounds();
    }

    self::persist();
  }

  /**
   * @return array
   */
  private static function empty_store() {
    return array(
      'ready'         => false,
      'categories'    => array(),
      'attributes'    => array(),
      'custom_fields' => array(),
      'acf_fields'    => array(),
      'price'         => array(
        'min' => 0,
        'max' => 0,
      ),
    );
  }

  /**
   * @return void
   */
  private static function ensure_store() {
    if (self::$store !== null) {
      return;
    }

    $stored = get_option(self::OPTION_KEY, null);

    if (!is_array($stored)) {
      self::$store = self::empty_store();

      return;
    }

    $store = self::empty_store();

    foreach (array('ready', 'categories', 'attributes', 'custom_fields', 'acf_fields', 'price') as $key) {
      if (array_key_exists($key, $stored)) {
        $store[$key] = $stored[$key];
      }
    }

    if (!is_array($store['categories'])) {
      $store['categories'] = array();
    }

    if (!is_array($store['attributes'])) {
      $store['attributes'] = array();
    }

    if (!is_array($store['custom_fields'])) {
      $store['custom_fields'] = array();
    }

    if (!is_array($store['acf_fields'])) {
      $store['acf_fields'] = array();
    }

    if (!is_array($store['price'])) {
      $store['price'] = array('min' => 0, 'max' => 0);
    }

    self::$store = $store;
  }

  /**
   * @return array
   */
  private static function get_store() {
    self::ensure_store();

    return self::$store;
  }

  /**
   * @return void
   */
  private static function persist() {
    self::ensure_store();

    $existing = get_option(self::OPTION_KEY, null);

    if ($existing === null || $existing === false) {
      add_option(self::OPTION_KEY, self::$store, '', false);

      return;
    }

    update_option(self::OPTION_KEY, self::$store, false);
  }

  /**
   * @param array $map
   * @return array
   */
  private static function public_value_map($map) {
    $out = array();

    if (!is_array($map)) {
      return $out;
    }

    foreach ($map as $key => $values) {
      if (!is_array($values)) {
        continue;
      }

      $names = array();

      foreach ($values as $name => $count) {
        if ((int) $count < 1) {
          continue;
        }

        $names[] = (string) $name;
      }

      if (empty($names)) {
        continue;
      }

      sort($names, SORT_NATURAL | SORT_FLAG_CASE);
      $out[$key] = $names;
    }

    return $out;
  }

  /**
   * @param mixed $raw
   * @param array $allowed_keys
   * @return array
   */
  private static function sanitize_field_map($raw, $allowed_keys) {
    $filters = array();

    if (!is_array($raw) || empty($allowed_keys)) {
      return $filters;
    }

    $allowed = array_flip($allowed_keys);

    foreach ($raw as $key => $value) {
      $key   = sanitize_key($key);
      $value = sanitize_text_field((string) $value);

      if ($key === '' || $value === '' || !isset($allowed[$key])) {
        continue;
      }

      $filters[$key] = $value;
    }

    return $filters;
  }

  /**
   * @param int   $post_id
   * @param array $new_snapshot
   * @return void
   */
  private static function apply_snapshot($post_id, $new_snapshot) {
    $old = get_post_meta($post_id, self::SNAPSHOT_META, true);

    if (!is_array($old)) {
      $old = array();
    }

    if (!is_array($new_snapshot)) {
      $new_snapshot = array();
    }

    self::ensure_store();
    self::adjust_counts($old, -1);
    self::adjust_counts($new_snapshot, 1);

    if (empty($new_snapshot)) {
      delete_post_meta($post_id, self::SNAPSHOT_META);

      return;
    }

    update_post_meta($post_id, self::SNAPSHOT_META, $new_snapshot);
  }

  /**
   * @param array $snapshot
   * @param int   $direction
   * @return void
   */
  private static function adjust_counts($snapshot, $direction) {
    $direction = (int) $direction;

    if ($direction === 0 || empty($snapshot)) {
      return;
    }

    if (!empty($snapshot['categories']) && is_array($snapshot['categories'])) {
      foreach ($snapshot['categories'] as $category) {
        if (!is_array($category) || empty($category['id'])) {
          continue;
        }

        $id = (string) (int) $category['id'];

        if (!isset(self::$store['categories'][$id])) {
          self::$store['categories'][$id] = array(
            'name'  => isset($category['name']) ? (string) $category['name'] : $id,
            'count' => 0,
          );
        }

        if (!empty($category['name'])) {
          self::$store['categories'][$id]['name'] = (string) $category['name'];
        }

        self::$store['categories'][$id]['count'] = (int) self::$store['categories'][$id]['count'] + $direction;

        if (self::$store['categories'][$id]['count'] <= 0) {
          unset(self::$store['categories'][$id]);
        }
      }
    }

    if (!empty($snapshot['attributes']) && is_array($snapshot['attributes'])) {
      foreach ($snapshot['attributes'] as $key => $block) {
        if (!is_array($block) || empty($block['values']) || !is_array($block['values'])) {
          continue;
        }

        $key = (string) $key;

        if (!isset(self::$store['attributes'][$key]) || !is_array(self::$store['attributes'][$key])) {
          self::$store['attributes'][$key] = array(
            'label'  => isset($block['label']) ? (string) $block['label'] : $key,
            'values' => array(),
          );
        }

        if (!empty($block['label'])) {
          self::$store['attributes'][$key]['label'] = (string) $block['label'];
        }

        foreach ($block['values'] as $value) {
          if (!is_array($value) || empty($value['slug'])) {
            continue;
          }

          $slug = (string) $value['slug'];

          if (!isset(self::$store['attributes'][$key]['values'][$slug])) {
            self::$store['attributes'][$key]['values'][$slug] = array(
              'name'  => isset($value['name']) ? (string) $value['name'] : $slug,
              'count' => 0,
            );
          }

          if (!empty($value['name'])) {
            self::$store['attributes'][$key]['values'][$slug]['name'] = (string) $value['name'];
          }

          self::$store['attributes'][$key]['values'][$slug]['count'] = (int) self::$store['attributes'][$key]['values'][$slug]['count'] + $direction;

          if (self::$store['attributes'][$key]['values'][$slug]['count'] <= 0) {
            unset(self::$store['attributes'][$key]['values'][$slug]);
          }
        }

        if (empty(self::$store['attributes'][$key]['values'])) {
          unset(self::$store['attributes'][$key]);
        }
      }
    }

    self::adjust_field_counts('custom_fields', isset($snapshot['custom_fields']) ? $snapshot['custom_fields'] : array(), $direction);
    self::adjust_field_counts('acf_fields', isset($snapshot['acf_fields']) ? $snapshot['acf_fields'] : array(), $direction);
  }

  /**
   * @param string $bucket
   * @param mixed  $fields
   * @param int    $direction
   * @return void
   */
  private static function adjust_field_counts($bucket, $fields, $direction) {
    if (!is_array($fields)) {
      return;
    }

    foreach ($fields as $key => $values) {
      $key = (string) $key;

      if (!is_array($values)) {
        continue;
      }

      if (!isset(self::$store[$bucket][$key]) || !is_array(self::$store[$bucket][$key])) {
        self::$store[$bucket][$key] = array();
      }

      foreach ($values as $value) {
        $value = (string) $value;

        if ($value === '') {
          continue;
        }

        if (!isset(self::$store[$bucket][$key][$value])) {
          self::$store[$bucket][$key][$value] = 0;
        }

        self::$store[$bucket][$key][$value] = (int) self::$store[$bucket][$key][$value] + $direction;

        if (self::$store[$bucket][$key][$value] <= 0) {
          unset(self::$store[$bucket][$key][$value]);
        }
      }

      if (empty(self::$store[$bucket][$key])) {
        unset(self::$store[$bucket][$key]);
      }
    }
  }

  /**
   * @param int $post_id
   * @return array
   */
  private static function build_snapshot($post_id) {
    $post = get_post($post_id);

    if (!$post || $post->post_type !== 'product' || $post->post_status !== 'publish') {
      return array();
    }

    if (!function_exists('wc_get_product')) {
      return array();
    }

    $product = wc_get_product($post_id);

    if (!$product || $product->get_catalog_visibility() === 'hidden') {
      return array();
    }

    if (!$product->is_in_stock() && (!self::out_of_stock_fallback_enabled() || self::has_visible_in_stock())) {
      return array();
    }

    $snapshot = array();

    if (Utils::get_option('filters_category_enabled')) {
      $categories = Utils::get_product_categories_for_filter($post_id);

      if (!empty($categories)) {
        $snapshot['categories'] = $categories;
      }
    }

    $allowed_attributes = Utils::get_filters_attributes_allowed_keys();

    if (!empty($allowed_attributes)) {
      $attributes = Utils::get_product_attributes_for_filter($post_id, $allowed_attributes, $product);

      if (!empty($attributes)) {
        $snapshot['attributes'] = $attributes;
      }
    }

    $custom_keys = Utils::get_filters_custom_fields_ordered_keys();
    $custom      = self::collect_meta_values($post_id, $product, $custom_keys);

    if (!empty($custom)) {
      $snapshot['custom_fields'] = $custom;
    }

    $acf_keys = Utils::get_filters_acf_fields_ordered_keys();
    $acf      = self::collect_meta_values($post_id, $product, $acf_keys);

    if (!empty($acf)) {
      $snapshot['acf_fields'] = $acf;
    }

    return $snapshot;
  }

  /**
   * @param int    $post_id
   * @param object $product
   * @param array  $keys
   * @return array
   */
  private static function collect_meta_values($post_id, $product, $keys) {
    $collected = array();

    if (empty($keys)) {
      return $collected;
    }

    foreach ($keys as $key) {
      $values = Utils::get_product_filter_meta_values($post_id, $key, $product);

      if (!empty($values)) {
        $collected[$key] = array_values($values);
      }
    }

    return $collected;
  }

  /**
   * @return bool
   */
  private static function out_of_stock_fallback_enabled() {
    $products = Utils::get_option('products');

    return is_array($products) && !empty($products['out_of_stock_fallback']);
  }

  /**
   * @param array  $filters
   * @param int    $limit
   * @param string $featured_mode any|only|exclude
   * @param bool   $in_stock
   * @return array
   */
  private static function query_id_slice($filters, $limit, $featured_mode, $in_stock) {
    $ids = self::run_product_sql('ids', $filters, $featured_mode, $in_stock, $limit);

    if (!is_array($ids)) {
      return array();
    }

    return array_map('intval', $ids);
  }

  /**
   * @return array
   */
  private static function query_price_bounds() {
    $in_stock = true;

    if (!self::has_visible_in_stock() && self::out_of_stock_fallback_enabled()) {
      $in_stock = false;
    }

    $row = self::run_product_sql('price', array(), '', $in_stock, 0);

    if (!is_array($row) || $row['min_price'] === null) {
      return array(
        'min' => 0,
        'max' => 0,
      );
    }

    return array(
      'min' => (float) $row['min_price'],
      'max' => (float) $row['max_price'],
    );
  }

  /**
   * @return bool
   */
  private static function query_has_visible_in_stock() {
    $product_id = self::run_product_sql('has', array(), '', true, 0);

    return !empty($product_id);
  }

  /**
   * Run a product query. Each clause is prepared before it is joined so values stay escaped while filters change the joins.
   *
   * @param string $kind          ids, price, or has.
   * @param array  $filters       Active filters. Used for id queries.
   * @param string $featured_mode any, only, or exclude.
   * @param bool   $in_stock      Limit to in-stock products.
   * @param int    $limit         Row limit for id queries.
   * @return mixed
   */
  private static function run_product_sql($kind, $filters, $featured_mode, $in_stock, $limit) {
    global $wpdb;

    if ($kind === 'price') {
      $sql = $wpdb->prepare(
        "SELECT MIN(CAST(price.meta_value AS DECIMAL(20,4))) AS min_price, MAX(CAST(price.meta_value AS DECIMAL(20,4))) AS max_price
        FROM %i p
        INNER JOIN %i price ON p.ID = price.post_id AND price.meta_key = %s AND price.meta_value <> ''
        WHERE p.post_type = %s AND p.post_status = 'publish'",
        $wpdb->posts,
        $wpdb->postmeta,
        '_price',
        'product'
      );
    } else {
      $sql = $wpdb->prepare(
        "SELECT p.ID FROM %i p WHERE p.post_type = %s AND p.post_status = 'publish'",
        $wpdb->posts,
        'product'
      );
    }

    $sql .= $wpdb->prepare(
      " AND NOT (
        EXISTS (
          SELECT 1
          FROM %i tr_hidden
          INNER JOIN %i tt_hidden ON tr_hidden.term_taxonomy_id = tt_hidden.term_taxonomy_id
          INNER JOIN %i t_hidden ON tt_hidden.term_id = t_hidden.term_id
          WHERE tr_hidden.object_id = p.ID
          AND tt_hidden.taxonomy = %s
          AND t_hidden.slug = %s
        )
        AND EXISTS (
          SELECT 1
          FROM %i tr_hidden2
          INNER JOIN %i tt_hidden2 ON tr_hidden2.term_taxonomy_id = tt_hidden2.term_taxonomy_id
          INNER JOIN %i t_hidden2 ON tt_hidden2.term_id = t_hidden2.term_id
          WHERE tr_hidden2.object_id = p.ID
          AND tt_hidden2.taxonomy = %s
          AND t_hidden2.slug = %s
        )
      )",
      $wpdb->term_relationships,
      $wpdb->term_taxonomy,
      $wpdb->terms,
      'product_visibility',
      'exclude-from-catalog',
      $wpdb->term_relationships,
      $wpdb->term_taxonomy,
      $wpdb->terms,
      'product_visibility',
      'exclude-from-search'
    );

    if ($in_stock) {
      $sql .= $wpdb->prepare(
        " AND NOT EXISTS (
          SELECT 1 FROM %i stock
          WHERE stock.post_id = p.ID
          AND stock.meta_key = %s
          AND stock.meta_value = %s
        )",
        $wpdb->postmeta,
        '_stock_status',
        'outofstock'
      );
    } else {
      $sql .= $wpdb->prepare(
        " AND EXISTS (
          SELECT 1 FROM %i stock
          WHERE stock.post_id = p.ID
          AND stock.meta_key = %s
          AND stock.meta_value = %s
        )",
        $wpdb->postmeta,
        '_stock_status',
        'outofstock'
      );
    }

    if ($kind === 'ids') {
      if (!empty($filters['category'])) {
        $sql .= $wpdb->prepare(
          " AND EXISTS (
            SELECT 1
            FROM %i tr_cat
            INNER JOIN %i tt_cat ON tr_cat.term_taxonomy_id = tt_cat.term_taxonomy_id
            WHERE tr_cat.object_id = p.ID
            AND tt_cat.taxonomy = %s
            AND tt_cat.term_id = %d
          )",
          $wpdb->term_relationships,
          $wpdb->term_taxonomy,
          'product_cat',
          (int) $filters['category']
        );
      }

      if (!empty($filters['rating'])) {
        $sql .= $wpdb->prepare(
          " AND EXISTS (
            SELECT 1 FROM %i rating
            WHERE rating.post_id = p.ID
            AND rating.meta_key = %s
            AND CAST(rating.meta_value AS DECIMAL(10,2)) >= %f
          )",
          $wpdb->postmeta,
          '_wc_average_rating',
          (float) $filters['rating']
        );
      }

      if (isset($filters['min_price']) && isset($filters['max_price'])) {
        $sql .= $wpdb->prepare(
          " AND EXISTS (
            SELECT 1 FROM %i price
            WHERE price.post_id = p.ID
            AND price.meta_key = %s
            AND price.meta_value <> ''
            AND CAST(price.meta_value AS DECIMAL(20,4)) >= %f
            AND CAST(price.meta_value AS DECIMAL(20,4)) <= %f
          )",
          $wpdb->postmeta,
          '_price',
          (float) $filters['min_price'],
          (float) $filters['max_price']
        );
      } elseif (isset($filters['min_price'])) {
        $sql .= $wpdb->prepare(
          " AND EXISTS (
            SELECT 1 FROM %i price
            WHERE price.post_id = p.ID
            AND price.meta_key = %s
            AND price.meta_value <> ''
            AND CAST(price.meta_value AS DECIMAL(20,4)) >= %f
          )",
          $wpdb->postmeta,
          '_price',
          (float) $filters['min_price']
        );
      } elseif (isset($filters['max_price'])) {
        $sql .= $wpdb->prepare(
          " AND EXISTS (
            SELECT 1 FROM %i price
            WHERE price.post_id = p.ID
            AND price.meta_key = %s
            AND price.meta_value <> ''
            AND CAST(price.meta_value AS DECIMAL(20,4)) <= %f
          )",
          $wpdb->postmeta,
          '_price',
          (float) $filters['max_price']
        );
      }

      if (!empty($filters['attributes']) && is_array($filters['attributes'])) {
        foreach ($filters['attributes'] as $key => $slug) {
          $name = self::attribute_value_name($key, $slug);

          if (strpos($key, 'pa_') === 0) {
            $sql .= $wpdb->prepare(
              " AND (
                EXISTS (
                  SELECT 1
                  FROM %i tr_attr
                  INNER JOIN %i tt_attr ON tr_attr.term_taxonomy_id = tt_attr.term_taxonomy_id
                  INNER JOIN %i t_attr ON tt_attr.term_id = t_attr.term_id
                  WHERE tr_attr.object_id = p.ID
                  AND tt_attr.taxonomy = %s
                  AND t_attr.slug = %s
                )
                OR EXISTS (
                  SELECT 1
                  FROM %i variation
                  INNER JOIN %i variation_meta ON variation.ID = variation_meta.post_id
                  WHERE variation.post_parent = p.ID
                  AND variation.post_type = %s
                  AND variation_meta.meta_key = %s
                  AND variation_meta.meta_value = %s
                )
              )",
              $wpdb->term_relationships,
              $wpdb->term_taxonomy,
              $wpdb->terms,
              $key,
              $slug,
              $wpdb->posts,
              $wpdb->postmeta,
              'product_variation',
              'attribute_' . $key,
              $slug
            );
          } else {
            $like_name = '%' . $wpdb->esc_like($name) . '%';

            $sql .= $wpdb->prepare(
              " AND (
                EXISTS (
                  SELECT 1
                  FROM %i variation
                  INNER JOIN %i variation_meta ON variation.ID = variation_meta.post_id
                  WHERE variation.post_parent = p.ID
                  AND variation.post_type = %s
                  AND variation_meta.meta_key LIKE %s
                  AND (variation_meta.meta_value = %s OR variation_meta.meta_value = %s)
                )
                OR EXISTS (
                  SELECT 1 FROM %i parent_attr
                  WHERE parent_attr.post_id = p.ID
                  AND parent_attr.meta_key = %s
                  AND parent_attr.meta_value LIKE %s
                )
              )",
              $wpdb->posts,
              $wpdb->postmeta,
              'product_variation',
              $wpdb->esc_like('attribute_') . '%',
              $slug,
              $name,
              $wpdb->postmeta,
              '_product_attributes',
              $like_name
            );
          }
        }
      }

      $field_groups = array();

      if (!empty($filters['custom_fields']) && is_array($filters['custom_fields'])) {
        $field_groups[] = $filters['custom_fields'];
      }

      if (!empty($filters['acf_fields']) && is_array($filters['acf_fields'])) {
        $field_groups[] = $filters['acf_fields'];
      }

      foreach ($field_groups as $fields) {
        foreach ($fields as $key => $value) {
          $like = '%' . $wpdb->esc_like('"' . $value . '"') . '%';

          $sql .= $wpdb->prepare(
            " AND (
              EXISTS (
                SELECT 1 FROM %i field_meta
                WHERE field_meta.post_id = p.ID
                AND field_meta.meta_key = %s
                AND (field_meta.meta_value = %s OR field_meta.meta_value LIKE %s)
              )
              OR EXISTS (
                SELECT 1
                FROM %i field_variation
                INNER JOIN %i field_variation_meta ON field_variation.ID = field_variation_meta.post_id
                WHERE field_variation.post_parent = p.ID
                AND field_variation.post_type = %s
                AND field_variation_meta.meta_key = %s
                AND (field_variation_meta.meta_value = %s OR field_variation_meta.meta_value LIKE %s)
              )
            )",
            $wpdb->postmeta,
            $key,
            $value,
            $like,
            $wpdb->posts,
            $wpdb->postmeta,
            'product_variation',
            $key,
            $value,
            $like
          );
        }
      }

      if ($featured_mode === 'exclude') {
        $sql .= $wpdb->prepare(
          " AND NOT EXISTS (
            SELECT 1
            FROM %i tr_featured
            INNER JOIN %i tt_featured ON tr_featured.term_taxonomy_id = tt_featured.term_taxonomy_id
            INNER JOIN %i t_featured ON tt_featured.term_id = t_featured.term_id
            WHERE tr_featured.object_id = p.ID
            AND tt_featured.taxonomy = %s
            AND t_featured.slug = %s
          )",
          $wpdb->term_relationships,
          $wpdb->term_taxonomy,
          $wpdb->terms,
          'product_visibility',
          'featured'
        );
      } elseif ($featured_mode === 'only') {
        $sql .= $wpdb->prepare(
          " AND EXISTS (
            SELECT 1
            FROM %i tr_featured
            INNER JOIN %i tt_featured ON tr_featured.term_taxonomy_id = tt_featured.term_taxonomy_id
            INNER JOIN %i t_featured ON tt_featured.term_id = t_featured.term_id
            WHERE tr_featured.object_id = p.ID
            AND tt_featured.taxonomy = %s
            AND t_featured.slug = %s
          )",
          $wpdb->term_relationships,
          $wpdb->term_taxonomy,
          $wpdb->terms,
          'product_visibility',
          'featured'
        );
      }

      $sql .= $wpdb->prepare(" ORDER BY p.post_date DESC LIMIT %d", (int) $limit);

      // The statement is already prepared in clauses. get_col() has to receive the finished statement.
      return $wpdb->get_col($sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    if ($kind === 'has') {
      $sql .= $wpdb->prepare(" LIMIT %d", 1);

      // The statement is already prepared in clauses. get_var() has to receive the finished statement.
      return $wpdb->get_var($sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    // The statement is already prepared in clauses. get_row() has to receive the finished statement.
    return $wpdb->get_row($sql, ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
  }

  /**
   * @param string $key
   * @param string $slug
   * @return string
   */
  private static function attribute_value_name($key, $slug) {
    $store = self::get_store();

    if (isset($store['attributes'][$key]['values'][$slug]['name'])) {
      return (string) $store['attributes'][$key]['values'][$slug]['name'];
    }

    return $slug;
  }

}
