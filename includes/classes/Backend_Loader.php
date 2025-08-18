<?php

namespace PolyPlugins\Speedy_Search;

use PolyPlugins\Speedy_Search\Backend\Admin;
use PolyPlugins\Speedy_Search\Backend\Admin\Settings;
use PolyPlugins\Speedy_Search\Backend\API;
use PolyPlugins\Speedy_Search\Backend\Background_Worker;
use PolyPlugins\Speedy_Search\Backend\Enqueue;
use PolyPlugins\Speedy_Search\Backend\Index_Updater;
use PolyPlugins\Speedy_Search\Backend\Notices;
use PolyPlugins\Speedy_Search\Backend\Reindexer;

class Backend_Loader {

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
    $this->load_settings();
    $this->load_api();
    $this->load_background_worker();
    $this->load_index_updater();
    $this->load_reindexer();
    $this->load_notices();
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
   * Load Admin
   *
   * @return void
   */
  public function load_settings() {
    $settings = new Settings($this->plugin, $this->version, $this->plugin_dir_url);
    $settings->init();
  }
  
  /**
   * Load Admin
   *
   * @return void
   */
  public function load_api() {
    $admin = new API($this->plugin, $this->version, $this->plugin_dir_url);
    $admin->init();
  }
  
  /**
   * Load Background Worker
   *
   * @return void
   */
  public function load_background_worker() {
    $background_worker = new Background_Worker($this->plugin, $this->version, $this->plugin_dir_url);
    $background_worker->init();
  }
  
  /**
   * Load Index Updater
   *
   * @return void
   */
  public function load_index_updater() {
    $index_updater = new Index_Updater($this->plugin, $this->version, $this->plugin_dir_url);
    $index_updater->init();
  }
  
  /**
   * Load Reindexer
   *
   * @return void
   */
  public function load_reindexer() {
    $reindexer = new Reindexer($this->plugin, $this->version, $this->plugin_dir_url);
    $reindexer->init();
  }
  
  /**
   * Load Notices
   *
   * @return void
   */
  public function load_notices() {
    $notices = new Notices($this->plugin, $this->version, $this->plugin_dir_url);
    $notices->init();
  }

}