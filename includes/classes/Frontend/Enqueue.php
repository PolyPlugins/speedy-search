<?php

namespace PolyPlugins\Speedy_Search\Frontend;

use PolyPlugins\Speedy_Search\Backend\DB;
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
	 * The options of this plugin.
	 *
	 * @var array $options The options array
	 */
  private $options;

  /**
	 * The popular options of this plugin.
	 *
	 * @var array $popular_options The popular options array
	 */
  private $popular_options;

  /**
	 * The advanced options of this plugin.
	 *
	 * @var array $advanced_options The advanced options array
	 */
  private $advanced_options;

  /**
	 * Is Snappy Search indexing?
	 *
	 * @var bool $is_indexing True if indexing, false if not
	 */
  private $is_indexing;
  
  /**
   * __construct
   *
   * @return void
   */
  public function __construct($plugin, $version) {
    $this->options          = Utils::get_options();
    $this->popular_options  = isset($this->options['popular']) ? $this->options['popular'] : array();
    $this->advanced_options = isset($this->options['advanced']) ? $this->options['advanced'] : array();
    $this->is_indexing      = Utils::is_indexing();
    $this->plugin           = $plugin;
    $this->version          = $version;
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
    $selector         = isset($this->options['selector']) ? $this->options['selector'] : '';
    $advanced_enabled = isset($this->advanced_options['enabled']) ? $this->advanced_options['enabled'] : '';

    if ($selector) {
      $this->enqueue_selector_search();
    } else {
      $this->enqueue_shortcode_search();
    }
    
    if ($advanced_enabled) {
      $this->enqueue_advanced_search();
    }
  }
  
  /**
   * Enqueue selector search
   *
   * @return void
   */
  private function enqueue_selector_search() {
    // Fallback to default search when indexing
    if (!$this->is_indexing) {
      wp_enqueue_script('snappy-search-selector', plugins_url('/js/frontend/selector.js', $this->plugin), array('jquery', 'wp-i18n'), $this->version, true);
      wp_localize_script(
        'snappy-search-selector',
        'snappy_search_object',
        array(
          'options'  => $this->options,
          'popular'  => DB::get_top_terms_last_x_days(),
          'currency' => class_exists('WooCommerce') ? get_woocommerce_currency_symbol() : '',
        )
      );
      wp_set_script_translations('snappy-search-selector', 'speedy-search', plugin_dir_path($this->plugin) . '/languages/');
      
      if ($this->popular_options['enabled']) {
        $this->enqueue_popular_search();
      }
    }
  }
  
  /**
   * Enqueue shortcode search
   *
   * @return void
   */
  private function enqueue_shortcode_search() {
    // Fallback to default search when indexing
    if (!$this->is_indexing) {
      wp_enqueue_script('snappy-search-shortcode', plugins_url('/js/frontend/shortcode.js', $this->plugin), array('jquery', 'wp-i18n'), $this->version, true);
      wp_localize_script(
        'snappy-search-shortcode',
        'snappy_search_object',
        array(
          'options'  => $this->options,
          'currency' => class_exists('WooCommerce') ? get_woocommerce_currency_symbol() : '',
        )
      );
      wp_set_script_translations('snappy-search-shortcode', 'speedy-search', plugin_dir_path($this->plugin) . '/languages/');
      
      if ($this->popular_options['enabled']) {
        $this->enqueue_popular_search();
      }
    }
  }
  
  /**
   * Enqueue advanced search
   *
   * @return void
   */
  private function enqueue_advanced_search() {
    // Fallback to default search when indexing
    if (!$this->is_indexing) {
      wp_enqueue_script('snappy-search-advanced', plugins_url('/js/frontend/advanced.js', $this->plugin), array('jquery', 'wp-i18n'), $this->version, true);
      wp_localize_script(
        'snappy-search-advanced',
        'snappy_search_object',
        array(
          'options'  => $this->options,
          'currency' => class_exists('WooCommerce') ? get_woocommerce_currency_symbol() : '',
        )
      );
      wp_set_script_translations('snappy-search-advanced', 'speedy-search', plugin_dir_path($this->plugin) . '/languages/');
      
      if ($this->popular_options['enabled']) {
        $this->enqueue_popular_search();
      }
    }
  }
  
  /**
   * Enqueue popular search
   *
   * @return void
   */
  private function enqueue_popular_search() {
    wp_enqueue_script('snappy-search-analytics', plugins_url('/js/frontend/analytics.js', $this->plugin), array('jquery'), $this->version, true);
    wp_localize_script(
      'snappy-search-analytics',
      'snappy_search_analytics_object',
      array(
        'options'  => $this->options,
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('speedy_search_analytics_nonce')
      )
    );
  }
  
}