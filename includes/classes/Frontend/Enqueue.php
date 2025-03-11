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
    wp_enqueue_script('speedy-search', plugins_url('/js/frontend/main.js', $this->plugin), array('jquery', 'wp-i18n'), $this->version, true);
    wp_localize_script(
      'speedy-search',
      'speedy_search_object',
      array(
        'selector' => Utils::get_option('selector'),
      )
    );
    wp_set_script_translations('speedy-search', 'speedy-search', plugin_dir_path($this->plugin) . '/languages/');
  }
  
}