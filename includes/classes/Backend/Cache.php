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
    add_action('woocommerce_product_set_stock_status', array($this, 'maybe_clear_api_cache_on_stock_status_change'), 10, 3);
    add_action('woocommerce_variation_set_stock_status', array($this, 'maybe_clear_api_cache_on_stock_status_change'), 10, 3);
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

    if ($stock_status !== 'outofstock') {
      return;
    }

    Utils::clear_api_cache();
  }

}
