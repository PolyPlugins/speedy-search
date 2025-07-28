<?php

namespace PolyPlugins\Speedy_Search\Backend;

use PolyPlugins\Speedy_Search\TNTSearch;
use PolyPlugins\Speedy_Search\Utils;

class Reindexer {

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
   * The TNTSearch index path
   *
   * @var string $index_path The TNTSearch index path
   */
  private $index_path;
  
  /**
   * __construct
   *
   * @return void
   */
  public function __construct($plugin, $version, $plugin_dir_url) {
    $this->index_path     = TNTSearch::get_instance()->get_index_path();
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
		add_action('wp_ajax_speedy_search_reindex_all', array($this, 'reindex_all'));
  }

  public function reindex_all() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'speedy_search_reindex_nonce')) {
      Utils::send_error('Invalid session', 403);
    }

    if (!current_user_can('manage_options')) {
      Utils::send_error('Unauthorized', 401);
    }

    Utils::reindex();

    Utils::send_success("Reindexing started");
  }

}