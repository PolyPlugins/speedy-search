<?php

namespace PolyPlugins\Speedy_Search;

class Translations_Loader {

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
    add_action('init', array($this, 'load_textdomain'));
  }

  /**
   * Load plugin text domain for translations
   * 
   * @return void
   */
  public function load_textdomain() {
    load_plugin_textdomain('speedy-search', false, dirname(plugin_basename($this->plugin)) . '/languages/');
  }

}