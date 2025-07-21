<?php

namespace PolyPlugins\Speedy_Search;

use PolyPlugins\Speedy_Search\Frontend\Analytics;
use PolyPlugins\Speedy_Search\Frontend\Enqueue;
use PolyPlugins\Speedy_Search\Frontend\Shortcode;
use PolyPlugins\Speedy_Search\Frontend\UI;

class Frontend_Loader {

  /**
	 * Full path and filename of plugin.
	 *
	 * @var string $version Full path and filename of plugin.
	 */
  private $plugin;

	/**
	 * The version of this plugin.
	 *
	 * @var   string $version The current version of this plugin.
	 */
	private $version;

  /**
   * The URL to the plugin directory.
   *
   * @var string $plugin_dir_url URL to the plugin directory.
   */
	private $plugin_dir_url;
  
  /**
   * __construct
   *
   * @return void
   */
  public function __construct($plugin, $version, $plugin_dir_url) {
    $this->plugin         = $plugin;
    $this->version        = $version;
    $this->plugin_dir_url = $plugin_dir_url;
  }
  
  /**
   * Init
   *
   * @return void
   */
  public function init() {
    $this->load_enqueue();
    $this->load_ui();
    $this->load_shortcode();
    $this->load_analytics();
  }
  
  /**
   * Load UI
   *
   * @return void
   */
  public function load_enqueue() {
    $gui = new Enqueue($this->plugin, $this->version);
    $gui->init();
  }
  
  /**
   * Load UI
   *
   * @return void
   */
  public function load_ui() {
    $gui = new UI($this->plugin, $this->version, $this->plugin_dir_url);
    $gui->init();
  }
  
  /**
   * Load Shortcode
   *
   * @return void
   */
  public function load_shortcode() {
    $shortcode = new Shortcode($this->plugin, $this->version, $this->plugin_dir_url);
    $shortcode->init();
  }
  
  /**
   * Load Analytics
   *
   * @return void
   */
  public function load_analytics() {
    $analytics = new Analytics($this->plugin, $this->version, $this->plugin_dir_url);
    $analytics->init();
  }

}