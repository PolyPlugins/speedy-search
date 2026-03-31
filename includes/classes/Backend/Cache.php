<?php

namespace PolyPlugins\Speedy_Search\Backend;

use PolyPlugins\Speedy_Search\Utils;

class Cache {

  private $plugin;
  private $version;
  private $plugin_dir_url;

  public function __construct($plugin, $version, $plugin_dir_url) {
    $this->plugin         = $plugin;
    $this->version        = $version;
    $this->plugin_dir_url = $plugin_dir_url;
  }

  public function init() {
    add_action('save_post', array($this, 'maybe_clear_api_cache_on_save_post'), 10, 3);
    add_action('delete_post', array($this, 'maybe_clear_api_cache_on_delete_post'));
    add_action('woocommerce_product_set_stock_status', array($this, 'maybe_clear_api_cache_on_stock_status_change'), 10, 3);
    add_action('woocommerce_variation_set_stock_status', array($this, 'maybe_clear_api_cache_on_stock_status_change'), 10, 3);
  }

  /**
   * Invalidate cached API search results when indexed content is saved
   *
   * @param int     $post_id Post ID.
   * @param WP_Post $post Post object.
   * @param bool    $update Whether this is an existing post being updated.
   * @return void
   */
  public function maybe_clear_api_cache_on_save_post($post_id, $post, $update) {
    if (!$post_id || !$post || wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
      return;
    }

    $post_type = $post->post_type;
    $allowed_types = Utils::get_allowed_post_types();

    if (!array_key_exists($post_type, $allowed_types)) {
      return;
    }

    $type    = $post_type . 's';
    $options = Utils::get_option($type);
    $enabled = isset($options['enabled']) ? $options['enabled'] : 1;

    if (!$enabled) {
      return;
    }

    Utils::clear_api_cache();
  }

  /**
   * Invalidate cached API search results when indexed content is deleted
   *
   * @param int $post_id Post ID.
   * @return void
   */
  public function maybe_clear_api_cache_on_delete_post($post_id) {
    if (!$post_id) {
      return;
    }

    $post = get_post($post_id);

    if (!$post) {
      return;
    }

    $post_type = $post->post_type;
    $allowed_types = Utils::get_allowed_post_types();

    if (!array_key_exists($post_type, $allowed_types)) {
      return;
    }

    $type    = $post_type . 's';
    $options = Utils::get_option($type);
    $enabled = isset($options['enabled']) ? $options['enabled'] : 1;

    if (!$enabled) {
      return;
    }

    Utils::clear_api_cache();
  }

  /**
   * Invalidate cached API search results when stock status changes
   *
   * @param int        $product_id Product ID.
   * @param string     $stock_status New stock status.
   * @param WC_Product $product Product object.
   * @return void
   */
  public function maybe_clear_api_cache_on_stock_status_change($product_id, $stock_status, $product) {
    if (!$product_id || !$stock_status) {
      return;
    }

    Utils::clear_api_cache();
  }

}
