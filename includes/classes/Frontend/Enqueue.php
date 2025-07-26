<?php

namespace PolyPlugins\Speedy_Search\Frontend;

use PolyPlugins\Speedy_Search\Utils;

class Enqueue {

  /**
	 * Full path and filename of plugin.
	 *
	 * @var string $version Full path and filename of plugin.
	 */
  private $plugin;

	/**
	 * The version of this plugin.
	 *
	 * @var string $version The current version of this plugin.
	 */
	private $version;
  
  /**
   * __construct
   *
   * @return void
   */
  public function __construct($plugin, $version) {
    $this->plugin  = $plugin;
    $this->version = $version;
  }
  
  /**
   * Init
   *
   * @return void
   */
  public function init() {
    add_action('wp_enqueue_scripts', array($this, 'enqueue'));
  }
  
  /**
   * Enqueue scripts and styles
   *
   * @return void
   */
  public function enqueue() {
    $this->enqueue_styles();
    $this->enqueue_scripts();
  }
  
  /**
   * Enqueue styles
   *
   * @return void
   */
  private function enqueue_styles() {
    wp_enqueue_style('style', plugins_url('/css/frontend/style.css', $this->plugin), array(), $this->version);
  }
  
  /**
   * Enqueue scripts
   *
   * @return void
   */
  private function enqueue_scripts() {
    $options                        = get_option('speedy_search_settings_polyplugins');
    $selector                       = isset($options['selector']) ? $options['selector'] : '';
    $posts_index                    = Utils::get_index('posts');
    $pages_index                    = Utils::get_index('pages');
    $products_index                 = Utils::get_index('products');
    $downloads_index                = Utils::get_index('downloads');
    $is_posts_indexing_complete     = isset($posts_index['complete']) ? true : false;
    $is_pages_indexing_complete     = isset($pages_index['complete']) ? true : false;
    $is_products_indexing_complete  = isset($products_index['complete']) ? true : false;
    $is_downloads_indexing_complete = isset($downloads_index['complete']) ? true : false;
    
    if ($selector) {
      // Fallback to default search when indexing
      if ($is_posts_indexing_complete || $is_pages_indexing_complete || $is_products_indexing_complete || $is_downloads_indexing_complete) {
        wp_enqueue_script('snappy-search-selector', plugins_url('/js/frontend/selector.js', $this->plugin), array('jquery', 'wp-i18n'), $this->version, true);
        wp_localize_script(
          'snappy-search-selector',
          'snappy_search_object',
          array(
            'options'  => $options,
            'currency' => class_exists('WooCommerce') ? get_woocommerce_currency_symbol() : '',
          )
        );
        wp_set_script_translations('snappy-search-selector', 'speedy-search', plugin_dir_path($this->plugin) . '/languages/');
        
        wp_enqueue_script('speedy-search-analytics', plugins_url('/js/frontend/analytics.js', $this->plugin), array('jquery'), $this->version, true);
        wp_localize_script(
          'snappy-search-analytics',
          'snappy_search_analytics_object',
          array(
            'options'  => $options,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('speedy_search_analytics_nonce')
          )
        );
      }
    } else {
      // Fallback to default search when indexing
      if ($is_posts_indexing_complete || $is_pages_indexing_complete || $is_products_indexing_complete || $is_downloads_indexing_complete) {
        wp_enqueue_script('snappy-search-shortcode', plugins_url('/js/frontend/shortcode.js', $this->plugin), array('jquery', 'wp-i18n'), $this->version, true);
        wp_localize_script(
          'snappy-search-shortcode',
          'snappy_search_object',
          array(
            'options'  => $options,
            'currency' => class_exists('WooCommerce') ? get_woocommerce_currency_symbol() : '',
          )
        );
        wp_set_script_translations('snappy-search-shortcode', 'speedy-search', plugin_dir_path($this->plugin) . '/languages/');
        
        wp_enqueue_script('snappy-search-analytics', plugins_url('/js/backend/analytics.js', $this->plugin), array('jquery'), $this->version, true);
        wp_localize_script(
          'snappy-search-analytics',
          'snappy_search_analytics_object',
          array(
            'options'  => $options,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('speedy_search_analytics_nonce')
          )
        );
      }
    }
  }
  
}