<?php

namespace PolyPlugins\Speedy_Search\Backend;

use PolyPlugins\Speedy_Search\Log;
use PolyPlugins\Speedy_Search\TNTSearch;
use PolyPlugins\Speedy_Search\Utils;
use WP_Query;

if (!defined('ABSPATH')) exit;

class CLI {

  /**
   * Register WP-CLI commands when WP-CLI is available.
   *
   * @return void
   */
  public static function register() {
    if (!defined('WP_CLI') || !WP_CLI) {
      return;
    }

    \WP_CLI::add_command('snappy-search', __CLASS__);

    Log::debug('Snappy Search WP-CLI commands registered.');
  }

  /**
   * Rebuild all Snappy Search indexes from scratch (runs to completion in this process).
   *
   * ## OPTIONS
   *
   * [--batch=<count>]
   * : Posts, pages, products, and downloads indexed per batch. Default: 500.
   *
   * [--batch-orders=<count>]
   * : WooCommerce orders per batch. Default: same as --batch.
   *
   * [--orders-only]
   * : Rebuild only the WooCommerce orders index. Does not clear or rebuild posts, pages, products, or downloads.
   *
   * ## EXAMPLES
   *
   *     wp snappy-search reindex
   *     wp snappy-search reindex --batch=500
   *     wp snappy-search reindex --batch=2000 --batch-orders=500
   *     wp snappy-search reindex --orders-only
   *     wp snappy-search reindex --orders-only --batch-orders=1000
   *
   * @when after_wp_load
   *
   * @param array $args       Positional arguments.
   * @param array $assoc_args Associative arguments.
   * @return void
   */
  public function reindex($args, $assoc_args) {
    $missing = Utils::is_missing_extensions();

    if ($missing !== false) {
      Log::error(sprintf('CLI reindex aborted: missing PHP extensions: %s', implode(', ', $missing)));

      \WP_CLI::error(sprintf('Missing PHP extensions: %s', implode(', ', $missing)));
    }

    $batch_default = 500;
    $batch         = isset($assoc_args['batch']) ? (int) $assoc_args['batch'] : $batch_default;
    $batch         = max(1, $batch);

    $batch_orders = isset($assoc_args['batch-orders']) ? (int) $assoc_args['batch-orders'] : $batch;
    $batch_orders = max(1, $batch_orders);

    $orders_only = !empty($assoc_args['orders-only']);

    if ($orders_only) {
      if (!class_exists('WooCommerce')) {
        Log::error('CLI reindex aborted: --orders-only requires WooCommerce.');

        \WP_CLI::error('--orders-only requires WooCommerce to be active.');
      }

      \WP_CLI::log('Clearing orders index only...');
      $this->cli_flush_orders_index();
      $this->cli_reset_orders_index_option();

      $tnt = TNTSearch::get_instance()->tnt();
      $this->cli_reindex_orders($tnt, $batch_orders);

      \WP_CLI::success('Orders reindexing finished.');

      return;
    }

    \WP_CLI::log('Clearing indexes...');
    Utils::reindex();
    $this->cli_flush_orders_index();

    $tnt = TNTSearch::get_instance()->tnt();

    $this->cli_reindex_posts($tnt, $batch);
    $this->cli_reindex_pages($tnt, $batch);

    if (class_exists('WooCommerce')) {
      $this->cli_reindex_products($tnt, $batch);
    }

    if (class_exists('Easy_Digital_Downloads')) {
      $this->cli_reindex_downloads($tnt, $batch);
    }

    if (class_exists('WooCommerce')) {
      $this->cli_reindex_orders($tnt, $batch_orders);
    }

    \WP_CLI::success('Reindexing finished.');
  }

  /**
   * Remove shop_orders progress from options without touching other index keys (for --orders-only).
   *
   * @return void
   */
  private function cli_reset_orders_index_option() {
    $indexes = Utils::get_indexes();

    if (!isset($indexes['shop_orders'])) {
      return;
    }

    unset($indexes['shop_orders']);

    update_option('speedy_search_indexes_polyplugins', $indexes);
  }

  /**
   * Drop persisted WooCommerce order index data (Utils::reindex does not cover shop_order).
   *
   * @return void
   */
  private function cli_flush_orders_index() {
    if (!class_exists('WooCommerce')) {
      return;
    }

    $database_type = Utils::get_option('database_type') ?: 'mysql';

    if ($database_type === 'mysql') {
      Utils::delete_db_index('shop_order');
    } else {
      $index_path = TNTSearch::get_instance()->get_index_path();
      Utils::delete_index($index_path, Utils::get_index_name('shop_order'));
    }
  }

  /**
   * @param object $tnt   TNT indexer instance.
   * @param int    $batch Batch size.
   * @return void
   */
  private function cli_reindex_posts($tnt, $batch) {
    $this->cli_reindex_single_post_type($tnt, 'post', __('Posts', 'speedy-search'), $batch);
  }

  /**
   * @param object $tnt   TNT indexer instance.
   * @param int    $batch Batch size.
   * @return void
   */
  private function cli_reindex_pages($tnt, $batch) {
    $this->cli_reindex_single_post_type($tnt, 'page', __('Pages', 'speedy-search'), $batch);
  }

  /**
   * @param object $tnt   TNT indexer instance.
   * @param int    $batch Batch size.
   * @return void
   */
  private function cli_reindex_products($tnt, $batch) {
    $this->cli_reindex_single_post_type($tnt, 'product', __('Products', 'speedy-search'), $batch);
  }

  /**
   * @param object $tnt   TNT indexer instance.
   * @param int    $batch Batch size.
   * @return void
   */
  private function cli_reindex_downloads($tnt, $batch) {
    $this->cli_reindex_single_post_type($tnt, 'download', __('Downloads', 'speedy-search'), $batch);
  }

  /**
   * CLI-only full index build for one content post type (does not use Background_Worker).
   *
   * @param object $tnt            TNT indexer instance.
   * @param string $post_type      Post type slug.
   * @param string $phase_label    Label for status lines.
   * @param int    $posts_per_page Batch size.
   * @return void
   */
  private function cli_reindex_single_post_type($tnt, $post_type, $phase_label, $posts_per_page) {
    $type    = $post_type . 's';
    $options = Utils::get_option($type);
    $enabled = isset($options['enabled']) ? $options['enabled'] : 1;

    if (!$enabled) {
      Utils::update_index($type, 'complete', true);
      \WP_CLI::log(sprintf('%s: skipped (disabled in settings).', $phase_label));

      return;
    }

    $counts    = wp_count_posts($post_type);
    $published = isset($counts->publish) ? (int) $counts->publish : 0;

    \WP_CLI::log(sprintf('%s: starting (%d published)...', $phase_label, $published));

    $index_name = Utils::get_index_name($post_type);
    $index_meta = Utils::get_index($type);

    if (!$index_meta) {
      $tnt->createIndex($index_name);
    }

    $tnt->selectIndex($index_name);

    $progress = (is_array($index_meta) && isset($index_meta['progress'])) ? $index_meta['progress'] : 1;

    while (true) {
      $query_args = array(
        'post_type'      => $post_type,
        'posts_per_page' => $posts_per_page,
        'offset'         => $progress,
        'orderby'        => 'date',
        'order'          => 'ASC',
        'post_status'    => 'publish',
      );

      $query = new WP_Query($query_args);

      if (!$query->have_posts()) {
        Utils::update_index($type, 'complete', true);
        \WP_CLI::log(sprintf('%s: complete (finished at position %d).', $phase_label, max(0, $progress - 1)));
        wp_reset_postdata();

        break;
      }

      $index_engine           = $tnt->getIndex();
      $progress_before_batch  = $progress;

      while ($query->have_posts()) {
        $query->the_post();

        $post_id = get_the_ID();
        $title   = get_the_title();
        $content = get_the_content();

        $doc = array(
          'id'      => intval($post_id),
          'title'   => sanitize_text_field($title),
          'content' => sanitize_text_field($content),
          'author'  => Utils::get_index_author_text($post_id),
        );

        $doc = Utils::add_taxonomy_terms_to_document($post_id, $doc);

        if ($post_type === 'product') {
          $product = wc_get_product($post_id);

          if (!$product) {
            $progress++;
            Utils::update_index($type, 'progress', $progress);

            continue;
          }

          $visibility = $product->get_catalog_visibility();

          // If product does not have search visibility remove it
          if ($visibility === 'hidden' || $visibility === 'catalog') {
            $progress++;
            Utils::update_index($type, 'progress', $progress);

            continue;
          }

          $sku                         = get_post_meta($post_id, '_sku', true);
          $doc['sku']                  = sanitize_text_field($sku);
          $doc['sku_normalized']       = sanitize_text_field(strtolower(str_replace('-', '', $sku)));
          $doc                         = Utils::add_product_custom_fields_to_document($post_id, $doc, $product);
        }

        $doc = Utils::apply_index_field_settings_to_document($post_type, $doc);
        $doc = Utils::sanitize_index_document($doc, 255);
        $index_engine->insert($doc);

        $progress++;

        Utils::update_index($type, 'progress', $progress);
      }

      wp_reset_postdata();

      $rows_this_batch = $progress - $progress_before_batch;

      \WP_CLI::log(sprintf(
        '%s: processed %d rows this batch — %d offset / %d published.',
        $phase_label,
        $rows_this_batch,
        max(0, $progress - 1),
        $published
      ));
    }
  }

  /**
   * CLI-only full WooCommerce orders index build.
   *
   * @param object $tnt   TNT indexer instance.
   * @param int    $limit Orders per batch.
   * @return void
   */
  private function cli_reindex_orders($tnt, $limit) {
    $post_type = 'shop_order';
    $type      = $post_type . 's';
    $options   = Utils::get_option('orders');
    $enabled   = isset($options['enabled']) ? $options['enabled'] : 1;

    if (!$enabled) {
      Utils::update_index($type, 'complete', true);
      \WP_CLI::log(sprintf('%s: skipped (disabled in settings).', __('Orders', 'speedy-search')));

      return;
    }

    \WP_CLI::log(__('Orders: starting...', 'speedy-search'));

    $index_name = Utils::get_index_name($post_type);
    $index_meta = Utils::get_index($type);

    if (!$index_meta) {
      $tnt->createIndex($index_name);
    }

    $tnt->selectIndex($index_name);

    $progress = (is_array($index_meta) && isset($index_meta['progress'])) ? $index_meta['progress'] : 1;

    while (true) {
      $order_args = array(
        'limit'   => $limit,
        'offset'  => $progress - 1,
        'type'    => 'shop_order',
        'orderby' => 'date',
        'order'   => 'ASC',
        'return'  => 'objects',
      );

      $orders = wc_get_orders($order_args);

      if (empty($orders)) {
        Utils::update_index($type, 'complete', true);
        \WP_CLI::log(sprintf(__('Orders: complete (finished at position %d).', 'speedy-search'), max(0, $progress - 1)));

        break;
      }

      $index_engine          = $tnt->getIndex();
      $progress_before_batch = $progress;

      foreach ($orders as $order) {
        $order_id = $order->get_id();
        $order_number = method_exists($order, 'get_order_number')
          ? $order->get_order_number()
          : $order_id;

        $doc = array(
          'id'                  => intval($order_id),
          'order_number'        => sanitize_text_field((string) $order_number),
          'billing_first_name'  => sanitize_text_field($order->get_billing_first_name()),
          'billing_last_name'   => sanitize_text_field($order->get_billing_last_name()),
          'billing_address_1'   => sanitize_text_field($order->get_billing_address_1()),
          'billing_address_2'   => sanitize_text_field($order->get_billing_address_2()),
          'billing_city'        => sanitize_text_field($order->get_billing_city()),
          'billing_email'       => sanitize_email($order->get_billing_email()),
          'billing_phone'       => sanitize_text_field($order->get_billing_phone()),
          'shipping_first_name' => sanitize_text_field($order->get_shipping_first_name()),
          'shipping_last_name'  => sanitize_text_field($order->get_shipping_last_name()),
          'shipping_address_1'  => sanitize_text_field($order->get_shipping_address_1()),
          'shipping_address_2'  => sanitize_text_field($order->get_shipping_address_2()),
          'shipping_city'       => sanitize_text_field($order->get_shipping_city()),
        );

        $doc = Utils::sanitize_index_document($doc, 255);
        $index_engine->insert($doc);
        $progress++;

        Utils::update_index($type, 'progress', $progress);
      }

      $rows_this_batch = $progress - $progress_before_batch;

      \WP_CLI::log(sprintf(
        __('Orders: processed %d orders this batch — %d indexed so far.', 'speedy-search'),
        $rows_this_batch,
        max(0, $progress - 1)
      ));
    }
  }

}
