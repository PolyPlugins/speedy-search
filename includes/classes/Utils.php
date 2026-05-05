<?php

namespace PolyPlugins\Speedy_Search;

use PolyPlugins\Speedy_Search\TNTSearch;

if (!defined('ABSPATH')) exit;

class Utils {

  /**
   * Get speedy search options
   *
   * @return array $options The speedy search options
   */
  public static function get_options() {
    $options = get_option('speedy_search_settings_polyplugins');

    return $options;
  }
  
  /**
   * Get speedy search option from options array
   *
   * @param  string $option The option to retrieve from options
   * @return mixed  $option The retrieved option value
   */
  public static function get_option($option) {
    $options = self::get_options();
    $option  = isset($options[$option]) ? $options[$option] : false;

    return $option;
  }
  
  /**
   * Update an option
   *
   * @param  string $option The option name
   * @param  mixed  $value  The option value
   * @return void
   */
  public static function update_option($option, $value) {
    $options          = self::get_options();
    $options[$option] = $value;

    update_option('speedy_search_settings_polyplugins', $options);
  }

  /**
   * Stored plugin version read from the options table only (bypasses cache).
   *
   * @return string|false
   */
  public static function get_current_version() {
    global $wpdb;

    $value = $wpdb->get_var(
      $wpdb->prepare(
        "SELECT option_value FROM %i WHERE option_name = %s LIMIT 1",
        $wpdb->options,
        'speedy_search_version_polyplugins'
      )
    );

    if ($value === null) {
      return false;
    }

    return maybe_unserialize($value);
  }

  /**
   * Persist plugin version in the options table only (bypasses stale reads; refreshes object cache for this option).
   *
   * @param  string $version Version string to store.
   * @return bool            False on database error, true otherwise.
   */
  public static function update_current_version($version) {
    global $wpdb;

    $version = sanitize_text_field((string) $version);
    $value   = maybe_serialize($version);

    $result = $wpdb->query(
      $wpdb->prepare(
        "INSERT INTO %i (option_name, option_value, autoload) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE option_value = %s",
        $wpdb->options,
        'speedy_search_version_polyplugins',
        $value,
        'auto',
        $value
      )
    );

    if ($result === false) {
      Log::error('Snappy Search failed to persist version option.');

      return false;
    }

    return true;
  }

  /**
   * Get API cache value
   *
   * @param  string $key Cache key
   * @return mixed
   */
  public static function get_api_cache($key) {
    return wp_cache_get($key, 'speedy_search_api');
  }

  /**
   * Set API cache value and register the key
   *
   * @param  string $key Cache key
   * @param  mixed  $value Cache value
   * @param  int    $expiration Cache expiration in seconds
   * @return void
   */
  public static function set_api_cache($key, $value, $expiration = 600) {
    wp_cache_set($key, $value, 'speedy_search_api', $expiration);

    $keys = get_option('speedy_search_api_cache_keys', array());

    if (!is_array($keys)) {
      $keys = array();
    }

    $keys[$key] = time();

    update_option('speedy_search_api_cache_keys', $keys);
  }

  /**
   * Clear API cache group
   *
   * @return void
   */
  public static function clear_api_cache() {
    $did_flush_group = false;

    if (function_exists('wp_cache_flush_group')) {
      if (function_exists('wp_cache_supports')) {
        if (wp_cache_supports('flush_group')) {
          wp_cache_flush_group('speedy_search_api');
          $did_flush_group = true;
        }
      } else {
        wp_cache_flush_group('speedy_search_api');
        $did_flush_group = true;
      }
    }

    if (!$did_flush_group) {
      $keys = get_option('speedy_search_api_cache_keys', array());

      if (is_array($keys) && !empty($keys)) {
        foreach ($keys as $key => $timestamp) {
          wp_cache_delete($key, 'speedy_search_api');
        }
      }
    }

    delete_option('speedy_search_api_cache_keys');
  }

  /**
   * Get speedy search indexes
   *
   * @return array $options The speedy search indexes options
   */
  public static function get_indexes() {
    $options = get_option('speedy_search_indexes_polyplugins');

    return $options ? $options : array();
  }
  
  /**
   * Get speedy search index from options array
   *
   * @param  string $option The option to retrieve from options
   * @return mixed  $option The retrieved option value
   */
  public static function get_index($option) {
    $options = self::get_indexes();
    $option  = isset($options[$option]) ? $options[$option] : false;

    return $option;
  }
  
  /**
   * Update an index
   *
   * @param  string $index  The index name
   * @param  string $option The option name
   * @param  mixed  $value  The option value
   * @return void
   */
  public static function update_index($index, $option, $value) {
    $options                  = self::get_indexes();
    $options[$index][$option] = $value;

    update_option('speedy_search_indexes_polyplugins', $options);
  }
  
  /**
   * Check if currently indexing
   *
   * @return bool $is_indexing The indexing status
   */
  public static function is_indexing() {
    $allowed_post_types = self::get_allowed_post_types();
    $is_indexing        = false;

    foreach($allowed_post_types as $allowed_post_type => $label) {
      $type        = $allowed_post_type . 's';
      $index       = self::get_index($type);
      $is_indexing = isset($index['complete']) ? false : true;

      if ($is_indexing) {
        return $is_indexing;
      }
    }

    return $is_indexing;
  }

  /**
   * Whether the WooCommerce orders search index has finished its initial build.
   *
   * @return bool
   */
  public static function is_orders_index_complete() {
    $index = self::get_index('shop_orders');

    return is_array($index) && !empty($index['complete']);
  }

  /**
   * Delete an index file
   *
   * @param  string $index_path The path to the index file
   * @param  string $filename   The index filename (e.g., 'posts.sqlite')
   * @return bool               True if deleted, false otherwise
   */
  public static function delete_index($index_path, $filename) {
    $path = rtrim($index_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    if (file_exists($path)) {
      return wp_delete_file($path);
    }

    return false;
  }
  
  /**
   * Flush DB index
   *
   * @param  string $post_type The post type
   * @return void
   */
  public static function delete_db_index($post_type) {
    $tnt        = TNTSearch::get_instance()->tnt();
    $index_name = self::get_index_name($post_type);

    try {
      $tnt->selectIndex($index_name);
    } catch (\Exception $e) {
      Log::debug(sprintf('delete_db_index: could not select index %s (%s)', $index_name, $e->getMessage()));

      return;
    }

    $tnt->engine->flushIndex($index_name);
  }
  
  /**
   * Reindex
   *
   * @return void
   */
  public static function reindex() {
    $index_path         = TNTSearch::get_instance()->get_index_path();
    $database_type      = self::get_option('database_type') ?: 'mysql';
    $allowed_post_types = self::get_allowed_post_types();

    if ($database_type === 'mysql') {
      // Reset indexing progress
      foreach ($allowed_post_types as $allowed_post_type => $label) {
        self::delete_db_index($allowed_post_type);
      }

      delete_option('speedy_search_indexes_polyplugins');
    } else {
      // Reset indexing progress
      foreach ($allowed_post_types as $allowed_post_type => $label) {
        $index_name = self::get_index_name($allowed_post_type);

        self::delete_index($index_path, $index_name);
      }

      delete_option('speedy_search_indexes_polyplugins');
    }
  }
  
  /**
   * Send success json
   *
   * @param  string $message The message to send
   * @param  int    $code    The status code
   * @return void
   */
  public static function send_success($message, $code = 200) {
    $message = $message ? sanitize_text_field($message) : __('Success', 'speedy-search');
    $code    = is_numeric($code) ? (int) $code : 200;

    wp_send_json_success(array(
      'message' => sanitize_text_field($message),
      'status' => $code
    ), $code);
  }
  
  /**
   * Send error json
   *
   * @param  mixed $message
   * @param  mixed $code
   * @return void
   */
  public static function send_error($message, $code = 400) {
    $message = $message ? sanitize_text_field($message) : __('Error', 'speedy-search');
    $code    = is_numeric($code) ? (int) $code : 400;

    Log::error(sprintf('Snappy Search request error (%d): %s', $code, $message));

    wp_send_json_error(array(
      'message' => sanitize_text_field($message),
      'status' => $code
    ), $code);
  }
  
  /**
   * Get allowed post types
   *
   * @return array $allowed_post_types The allowed post types
   */
  public static function get_allowed_post_types() {
    $allowed_post_types = array(
      'post' => __('Post', 'speedy-search'),
      'page' => __('Page', 'speedy-search')
    );

    if (class_exists('WooCommerce')) {
      $allowed_post_types['product'] = __('Product', 'speedy-search');
    }

    if (class_exists('Easy_Digital_Downloads')) {
      $allowed_post_types['download'] = __('Download', 'speedy-search');
    }

    return $allowed_post_types;
  }
  
  /**
   * Get allowed types
   *
   * @return array $allowed_types The allowed types
   */
  public static function get_allowed_types() {
    $allowed_post_types = self::get_allowed_post_types();

    $allowed_types = array();

    foreach ($allowed_post_types as $key => $label) {
      $key                 = $key . 's';
      $allowed_types[$key] = $label . __('s', 'speedy-search');
    }

    return $allowed_types;
  }
  
  /**
   * Get index name
   *
   * @param  string $post_type  The type of index
   * @return string $index      The index name
   */
  public static function get_index_name($post_type) {
    $type          = $post_type . 's';
		$database_type = self::get_option('database_type') ?: 'mysql';
    $index_name    = '';

    if ($database_type === 'mysql') {
      $index_name = 'wp_ss_' . $type;
    } else {
      $index_name = $type . '.sqlite';
    }

    return $index_name;
  }

  /**
   * Get the slug of the page using the advanced search template
   *
   * @return string|false
   */
  public static function get_page_slug_by_template() {
    $args = array(
      'post_type'      => 'page',
      'posts_per_page' => 1,
      'post_status'    => 'publish',
      'meta_key'       => '_wp_page_template',
      'meta_value'     => 'snappy-search-advanced-search-form.php',
      'fields'         => 'ids',
    );

    $default = get_posts($args);

    if (!empty($default)) {
      $slug = get_post_field('post_name', $default[0]);

      return $slug;
    } else {
      $args = array(
        'post_type'      => 'page',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
        'meta_key'       => '_wp_page_template',
        'meta_value'     => 'snappy-search-advanced-search-form-stacked.php',
        'fields'         => 'ids',
      );

      $stacked = get_posts($args);

      if (!empty($stacked)) {
        $slug = get_post_field('post_name', $stacked[0]);

        return $slug;
      }
    }

    return false;
  }
  
  /**
   * Checks for any missing extensions
   *
   * @return mixed $is_missing_extension Array of missing extensions or false
   */
  public static function is_missing_extensions() {
		$database_type      = self::get_option('database_type') ?: 'mysql';
    $missing_extensions = array();

    $extensions = array(
      'PDO',
      'mbstring'
    );
    
    if ($database_type === 'sqlite') {
      $extensions[] = 'pdo_sqlite';
    }

    foreach ($extensions as $extension) {
      if (!extension_loaded($extension)) {
        $missing_extensions[] = $extension;
      }
    }

    $is_missing_extensions = $missing_extensions ? $missing_extensions : false;

    return $is_missing_extensions;
  }

  /**
   * Convert Hex color to RGBA
   *
   * @param  mixed $hex
   * @param  mixed $alpha
   * @return void
   */
  public static function hex_to_rgba($hex, $alpha = null) {
    // Remove the '#' if present
    $hex = ltrim($hex, '#');
    
    // Get the red, green, and blue values
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    // If alpha is provided, return rgba format, otherwise return rgb
    if ($alpha !== null) {
      if ($alpha > 1) {
        $alpha = $alpha / 100; // Convert percentage to decimal
      }

      return "rgba($r, $g, $b, $alpha)";
    } else {
      return "rgb($r, $g, $b)";
    }
  }

  /**
   * Strip shortcodes and oversized tokens from plain text.
   *
   * @param string $value            Text to sanitize.
   * @param int    $max_token_length Maximum token length allowed.
   * @return string
   */
  public static function sanitize_index_text($value, $max_token_length = 255) {
    if (!is_string($value) || $value === '') {
      return $value;
    }

    if (function_exists('strip_shortcodes')) {
      $value = strip_shortcodes($value);
    }

    // Remove shortcode-like wrappers even if shortcode tags are not registered.
    $value = preg_replace('/\[(?:\/)?[a-zA-Z0-9_-]+(?:\s[^\]]*)?\]/', ' ', $value);

    $max_token_length = is_numeric($max_token_length) ? (int) $max_token_length : 255;

    if ($max_token_length < 1) {
      return $value;
    }

    $min_oversized_length = $max_token_length + 1;
    $pattern              = '/\S{' . $min_oversized_length . ',}/u';
    $sanitized            = preg_replace($pattern, ' ', (string) $value);

    // Fallback if unicode mode fails for edge-case invalid UTF-8.
    if ($sanitized === null) {
      $pattern   = '/\S{' . $min_oversized_length . ',}/';
      $sanitized = preg_replace($pattern, ' ', (string) $value);
    }

    return is_string($sanitized) ? $sanitized : $value;
  }

  /**
   * Strip oversized tokens from each string field in an index document.
   *
   * @param array $document          Key/value data passed to TNTSearch insert.
   * @param int   $max_token_length  Maximum token length allowed.
   * @return array
   */
  public static function sanitize_index_document($document, $max_token_length = 255) {
    if (!is_array($document)) {
      return $document;
    }

    foreach ($document as $key => $value) {
      if (!is_string($value)) {
        continue;
      }

      $document[$key] = self::sanitize_index_text($value, $max_token_length);
    }

    return $document;
  }

  /**
   * Text to index for the post author (display name, slug, login).
   * Only blog posts (`post`); pages, products, and other types return empty.
   *
   * @param int $post_id
   * @return string
   */
  public static function get_index_author_text($post_id) {
    $post_id = (int) $post_id;

    if ($post_id < 1) {
      return '';
    }

    $post = get_post($post_id);

    if (!$post) {
      return '';
    }

    if ($post->post_type !== 'post') {
      return '';
    }

    $author_id = (int) $post->post_author;

    if ($author_id < 1) {
      return '';
    }

    $user = get_userdata($author_id);

    if (!$user) {
      return '';
    }

    $raw = array(
      sanitize_text_field((string) $user->display_name),
      sanitize_text_field((string) $user->user_nicename),
      sanitize_text_field((string) $user->user_login),
    );

    $parts = array();

    foreach ($raw as $part) {
      if ($part === '') {
        continue;
      }

      // Skip bare emails (privacy / noise); keep non-email parts only.
      if (false !== is_email($part)) {
        continue;
      }

      $parts[] = $part;
    }

    $parts = array_unique($parts);

    return sanitize_text_field(implode(' ', $parts));
  }

  /**
   * WooCommerce product categories for client-side filter UI.
   *
   * @param int $product_id
   * @return array
   */
  public static function get_product_categories_for_filter($product_id) {
    $product_id = (int) $product_id;

    if ($product_id < 1 || !function_exists('taxonomy_exists') || !taxonomy_exists('product_cat')) {
      return array();
    }

    $terms = get_the_terms($product_id, 'product_cat');

    if (empty($terms) || is_wp_error($terms)) {
      return array();
    }

    $out = array();

    foreach ($terms as $term) {
      $out[] = array(
        'id'   => (int) $term->term_id,
        'name' => sanitize_text_field($term->name),
        'slug' => sanitize_title($term->slug),
      );
    }

    return $out;
  }

  /**
   * Normalize one Filters-tab attribute token to storage key (pa_*, attr_*).
   *
   * @param string $token
   * @return string
   */
  public static function normalize_filters_attribute_token($token) {
    $token = trim((string) $token);

    if ($token === '') {
      return '';
    }

    $lower = strtolower($token);

    if (strpos($lower, 'pa_') === 0) {
      return sanitize_key($lower);
    }

    if (strpos($lower, 'attr_') === 0) {
      return sanitize_key($lower);
    }

    $base = sanitize_title($token);

    if ($base === '') {
      return '';
    }

    if (function_exists('wc_attribute_taxonomy_name')) {
      return wc_attribute_taxonomy_name($base);
    }

    return 'pa_' . $base;
  }

  /**
   * Parsed attribute keys from filters_attributes option (comma-separated).
   *
   * @return array
   */
  public static function get_filters_attributes_allowed_keys() {
    $raw = self::get_option('filters_attributes');

    if (!is_string($raw) || trim($raw) === '') {
      return array();
    }

    $parts = array_filter(array_map('trim', explode(',', $raw)));
    $keys  = array();

    foreach ($parts as $p) {
      $k = self::normalize_filters_attribute_token($p);

      if ($k !== '') {
        $keys[] = $k;
      }
    }

    return array_values(array_unique($keys));
  }

  /**
   * Custom field meta keys from filters_custom_fields option in comma order (for filter UI ordering).
   *
   * @return array
   */
  public static function get_filters_custom_fields_ordered_keys() {
    $raw = self::get_option('filters_custom_fields');

    if (!is_string($raw) || trim($raw) === '') {
      return array();
    }

    $parts = array_filter(array_map('trim', explode(',', $raw)));
    $keys  = array();

    foreach ($parts as $p) {
      $k = sanitize_key($p);

      if ($k !== '') {
        $keys[] = $k;
      }
    }

    return array_values(array_unique($keys));
  }

  /**
   * WooCommerce product attributes for client-side filter UI (global taxonomies, local attributes, variations).
   *
   * @param int              $product_id
   * @param array            $allowed_keys Attribute keys from settings; empty array returns no attributes.
   * @param \WC_Product|null $wc_product   Optional product instance for this ID (avoids a duplicate wc_get_product).
   * @return array Attribute key => array with keys label, values (each value: slug, name).
   */
  public static function get_product_attributes_for_filter($product_id, $allowed_keys = array(), $wc_product = null) {
    $product_id = (int) $product_id;

    if ($product_id < 1 || !function_exists('wc_get_product')) {
      return array();
    }

    if (!is_array($allowed_keys) || empty($allowed_keys)) {
      return array();
    }

    $allowed_lookup = array_flip($allowed_keys);

    if (!$wc_product instanceof \WC_Product || (int) $wc_product->get_id() !== $product_id) {
      $wc_product = wc_get_product($product_id);
    }

    if (!$wc_product) {
      return array();
    }

    $slugmap = array();

    $ensure_key = function ($key, $label) use (&$slugmap) {
      if (!isset($slugmap[$key])) {
        $slugmap[$key] = array(
          'label' => $label,
          'terms' => array(),
        );
      }
    };

    $add_term = function ($key, $label, $slug, $name) use (&$slugmap, $ensure_key, $allowed_lookup) {
      if (!isset($allowed_lookup[$key])) {
        return;
      }

      $slug = sanitize_title((string) $slug);
      $name = sanitize_text_field((string) $name);

      if ($slug === '' || $name === '') {
        return;
      }

      $ensure_key($key, $label);
      $slugmap[$key]['terms'][$slug] = $name;
    };

    foreach ($wc_product->get_attributes() as $attr) {
      if (!$attr instanceof \WC_Product_Attribute) {
        continue;
      }

      $tax_name = $attr->get_name();
      $label    = wc_attribute_label($tax_name);

      if ($attr->is_taxonomy()) {
        foreach ((array) $attr->get_options() as $term_id) {
          $term = get_term((int) $term_id);

          if ($term && !is_wp_error($term)) {
            $add_term($tax_name, $label, $term->slug, $term->name);
          }
        }
      } else {
        $key = 'attr_' . sanitize_title($tax_name);

        foreach ((array) $attr->get_options() as $opt) {
          $opt = sanitize_text_field((string) $opt);

          if ($opt === '') {
            continue;
          }

          $add_term($key, $label, sanitize_title($opt), $opt);
        }
      }
    }

    if ($wc_product->is_type('variable')) {
      foreach ($wc_product->get_children() as $child_id) {
        $child = wc_get_product($child_id);

        if (!$child) {
          continue;
        }

        $va = $child->get_variation_attributes();

        if (empty($va) || !is_array($va)) {
          continue;
        }

        foreach ($va as $meta_key => $slug) {
          $slug = is_string($slug) ? trim($slug) : '';

          if ($slug === '') {
            continue;
          }

          $tax_name = str_replace('attribute_', '', (string) $meta_key);

          if (taxonomy_exists($tax_name)) {
            if (!isset($allowed_lookup[$tax_name])) {
              continue;
            }

            $term = get_term_by('slug', $slug, $tax_name);
            $nm   = ($term && !is_wp_error($term)) ? sanitize_text_field($term->name) : $slug;

            $add_term($tax_name, wc_attribute_label($tax_name), $slug, $nm);
          } else {
            $key = 'attr_' . sanitize_title($tax_name);

            if (!isset($allowed_lookup[$key])) {
              continue;
            }

            $nm = sanitize_text_field($slug);

            $add_term($key, sanitize_text_field(wc_clean($tax_name)), sanitize_title($slug), $nm);
          }
        }
      }
    }

    $needs_pa_terms_scan = false;

    foreach ($allowed_keys as $ak) {
      if (strpos((string) $ak, 'pa_') === 0) {
        $needs_pa_terms_scan = true;
        break;
      }
    }

    if ($needs_pa_terms_scan && function_exists('wc_get_attribute_taxonomies') && function_exists('wc_attribute_taxonomy_name') && function_exists('wc_get_product_terms')) {
      foreach (wc_get_attribute_taxonomies() as $tax_row) {
        $tax_name = wc_attribute_taxonomy_name($tax_row->attribute_name);

        if (!isset($allowed_lookup[$tax_name])) {
          continue;
        }

        $terms = wc_get_product_terms($product_id, $tax_name, array('fields' => 'all'));

        if (empty($terms) || is_wp_error($terms)) {
          continue;
        }

        $label = !empty($tax_row->attribute_label) ? sanitize_text_field($tax_row->attribute_label) : wc_attribute_label($tax_name);

        foreach ($terms as $term) {
          if (!isset($term->slug)) {
            continue;
          }

          $add_term($tax_name, $label, $term->slug, $term->name);
        }
      }
    }

    $out = array();

    foreach ($slugmap as $key => $data) {
      if (empty($data['terms'])) {
        continue;
      }

      $vals = array();

      foreach ($data['terms'] as $sl => $nm) {
        $vals[] = array(
          'slug' => $sl,
          'name' => $nm,
        );
      }

      usort($vals, function ($a, $b) {
        return strcmp($a['name'], $b['name']);
      });

      $out[$key] = array(
        'label'  => $data['label'],
        'values' => $vals,
      );
    }

    $filtered = array();

    foreach ($allowed_keys as $k) {
      if (isset($out[$k])) {
        $filtered[$k] = $out[$k];
      }
    }

    return $filtered;
  }

  /**
   * Saved indexing toggles merged with defaults (all on when unset).
   *
   * @return array
   */
  public static function get_indexing_field_options() {
    $defaults = array(
      'title'                 => true,
      'content'               => true,
      'categories'            => true,
      'tags'                  => true,
      'author'                => true,
      'product_sku'           => true,
      'product_custom_fields' => true,
    );

    $stored = self::get_option('indexing');

    if (!is_array($stored)) {
      return $defaults;
    }

    foreach ($defaults as $key => $default_on) {
      if (!array_key_exists($key, $stored)) {
        continue;
      }

      $defaults[$key] = !empty($stored[$key]);
    }

    return $defaults;
  }

  /**
   * How many indexing sources are enabled (used to enforce “at least one” in settings).
   *
   * @param array $idx Keys: title, content, categories, tags, optional product_* when WooCommerce is active.
   * @return int
   */
  public static function count_enabled_indexing_sources($idx) {
    if (!is_array($idx)) {
      return 0;
    }

    $n = 0;

    foreach (array('title', 'content', 'categories', 'tags', 'author') as $k) {
      if (!empty($idx[$k])) {
        $n++;
      }
    }

    if (class_exists('WooCommerce')) {
      foreach (array('product_sku', 'product_custom_fields') as $k) {
        if (!empty($idx[$k])) {
          $n++;
        }
      }
    }

    return $n;
  }

  /**
   * Remove document fields the admin turned off under Indexing settings.
   *
   * @param string $post_type
   * @param array  $document
   * @return array
   */
  public static function apply_index_field_settings_to_document($post_type, $document) {
    if (!is_array($document)) {
      return $document;
    }

    $opts = self::get_indexing_field_options();

    if (empty($opts['title'])) {
      $document['title'] = '';
    }

    if (empty($opts['content'])) {
      $document['content'] = '';
    }

    if (empty($opts['categories'])) {
      $document['category']    = '';
      $document['product_cat'] = '';
    }

    if (empty($opts['tags'])) {
      $document['post_tag']    = '';
      $document['product_tag'] = '';
    }

    if (empty($opts['author'])) {
      $document['author'] = '';
    }

    if ($post_type === 'product') {
      if (empty($opts['product_sku'])) {
        $document['sku']              = '';
        $document['sku_normalized'] = '';
      }

      if (empty($opts['product_custom_fields'])) {
        $custom_fields_raw = self::get_option('filters_custom_fields');
        $custom_fields     = $custom_fields_raw ? array_filter(array_map('trim', explode(',', $custom_fields_raw))) : array();

        foreach ($custom_fields as $custom_field) {
          $meta_key = sanitize_key($custom_field);

          if ($meta_key === '') {
            continue;
          }

          if (isset($document[$meta_key])) {
            unset($document[$meta_key]);
          }
        }
      }
    }

    return $document;
  }

  /**
   * Add taxonomy term names into the index document.
   *
   * @param int   $post_id
   * @param array $document
   * @param array $taxonomies
   * @return array
   */
  public static function add_taxonomy_terms_to_document($post_id, $document, $taxonomies = array()) {
    if (!is_array($document)) {
      return $document;
    }

    if (empty($taxonomies)) {
      $taxonomies = array('category', 'post_tag', 'product_cat', 'product_tag');
    }

    foreach ($taxonomies as $taxonomy) {
      $taxonomy_name = sanitize_key((string) $taxonomy);

      if ($taxonomy_name === '') {
        continue;
      }

      $term_names = wp_get_post_terms($post_id, $taxonomy_name, array('fields' => 'names'));

      if (is_wp_error($term_names) || empty($term_names)) {
        $document[$taxonomy_name] = '';
        continue;
      }

      $document[$taxonomy_name] = sanitize_text_field(implode(' ', $term_names));
    }

    return $document;
  }

  /**
   * Add configured product custom fields into the index document.
   *
   * @param int   $product_id
   * @param array $document
   * @param mixed $product
   * @return array
   */
  public static function add_product_custom_fields_to_document($product_id, $document, $product = null) {
    if (!is_array($document)) {
      return $document;
    }

    $custom_fields_raw = self::get_option('filters_custom_fields');
    $custom_fields     = $custom_fields_raw ? array_filter(array_map('trim', explode(',', $custom_fields_raw))) : array();

    if (empty($custom_fields)) {
      return $document;
    }

    foreach ($custom_fields as $custom_field) {
      $meta_key = sanitize_key($custom_field);

      if ($meta_key === '') {
        continue;
      }

      $values               = self::get_product_custom_field_values($product_id, $meta_key, $product);
      $document[$meta_key]  = sanitize_text_field(implode(' ', $values));
    }

    return $document;
  }

  /**
   * Get product custom field values from product and variations.
   *
   * @param int    $product_id
   * @param string $meta_key
   * @param mixed  $product
   * @return array
   */
  private static function get_product_custom_field_values($product_id, $meta_key, $product) {
    $values = self::normalize_custom_field_values(get_post_meta($product_id, $meta_key, true));

    if ($product && method_exists($product, 'is_type') && $product->is_type('variable')) {
      $variation_ids = $product->get_children();

      if (!empty($variation_ids)) {
        foreach ($variation_ids as $variation_id) {
          $variation_values = self::normalize_custom_field_values(get_post_meta($variation_id, $meta_key, true));

          if (!empty($variation_values)) {
            $values = array_merge($values, $variation_values);
          }
        }
      }
    }

    return array_values(array_unique($values));
  }

  /**
   * Normalize custom field values to a flat sanitized array.
   *
   * @param mixed $meta_value
   * @return array
   */
  private static function normalize_custom_field_values($meta_value) {
    $values = array();

    if (is_string($meta_value) && function_exists('maybe_unserialize')) {
      $meta_value = maybe_unserialize($meta_value);
    }

    if (is_array($meta_value)) {
      foreach ($meta_value as $value) {
        if (is_array($value)) {
          foreach ($value as $nested_value) {
            if (is_array($nested_value)) {
              continue;
            }

            $sanitized_value = sanitize_text_field((string) $nested_value);

            if ($sanitized_value !== '') {
              $values[] = $sanitized_value;
            }
          }

          continue;
        }

        $sanitized_value = sanitize_text_field((string) $value);

        if ($sanitized_value !== '') {
          $values[] = $sanitized_value;
        }
      }
    } else {
      $sanitized_value = sanitize_text_field((string) $meta_value);

      if ($sanitized_value !== '') {
        $values[] = $sanitized_value;
      }
    }

    return $values;
  }

  public static function prevent_predis_autoload_conflict($autoloader) {
    if (!is_object($autoloader) || !method_exists($autoloader, 'loadClass')) {
      return;
    }

    spl_autoload_unregister(array($autoloader, 'loadClass'));
    spl_autoload_register(function ($class) use ($autoloader) {
      if (strpos($class, 'Predis\\') === 0) {
        return false;
      }

      return $autoloader->loadClass($class);
    }, true, true);
  }

  /**
   * Get API endpoint URLs with custom search.php fallback support.
   *
   * @return array
   */
  public static function get_api_endpoints() {
    $version            = '1.0.0';
    $custom_search_file = ABSPATH . 'search.php';
    $has_custom_file    = file_exists($custom_search_file);

    if ($has_custom_file) {
      $base_url = add_query_arg(array(
        'snappy_search' => '1',
        'v'             => $version,
      ), home_url('/search.php'));

      return array(
        'has_custom_file' => true,
        'version'         => $version,
        'search'          => add_query_arg('endpoint', 'search', $base_url),
        'preload'         => add_query_arg('endpoint', 'preload', $base_url),
        'latest'          => add_query_arg('endpoint', 'latest', $base_url),
        'posts'           => add_query_arg('endpoint', 'posts', $base_url),
        'pages'           => add_query_arg('endpoint', 'pages', $base_url),
        'products'        => add_query_arg('endpoint', 'products', $base_url),
        'downloads'       => add_query_arg('endpoint', 'downloads', $base_url),
        // Orders stay on REST so existing permission callbacks remain enforced.
        'orders'          => home_url('/wp-json/speedy-search-search/v1/orders'),
      );
    }

    return array(
      'has_custom_file' => false,
      'version'         => $version,
      'search'          => home_url('/wp-json/speedy-search/v1/search/'),
      'preload'         => home_url('/wp-json/speedy-search/v1/preload/'),
      'latest'          => home_url('/wp-json/speedy-search/v1/latest/'),
      'posts'           => home_url('/wp-json/speedy-search/v1/posts/'),
      'pages'           => home_url('/wp-json/speedy-search/v1/pages/'),
      'products'        => home_url('/wp-json/speedy-search/v1/products/'),
      'downloads'       => home_url('/wp-json/speedy-search/v1/downloads/'),
      'orders'          => home_url('/wp-json/speedy-search-search/v1/orders'),
    );
  }

}