<?php

/**
 * Plugin Name: Snappy Search
 * Description: A fast, lightweight search plugin powered by TNTSearch, indexing posts for instant, accurate results.
 * Version: 1.5.0
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: Poly Plugins
 * Author URI: https://www.polyplugins.com
 * Plugin URI: https://www.polyplugins.com/contact/
 * Text Domain: speedy-search
 * License: GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

namespace PolyPlugins\Speedy_Search;

if (!defined('ABSPATH')) exit;

require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

register_activation_hook(__FILE__, array(__NAMESPACE__ . '\Activation', 'init'));
register_deactivation_hook(__FILE__, array(__NAMESPACE__ . '\Deactivation', 'init'));

class Speedy_Search
{
  
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
  public function __construct() {
    $this->plugin         = __FILE__;
    $this->version        = $this->get_plugin_version();
    $this->plugin_dir_url = untrailingslashit(plugin_dir_url($this->plugin));
  }
  
  /**
   * Init
   *
   * @return void
   */
  public function init() {
    $this->load_dependencies();
  }
  
  /**
   * Load dependencies
   *
   * @return void
   */
  public function load_dependencies() {
    $dependency_loader = new Dependency_Loader($this->plugin, $this->version, $this->plugin_dir_url);
    $dependency_loader->init();
  }

  /**
   * Get the plugin version
   *
   * @return string $version The plugin version
   */
  private function get_plugin_version() {
    $plugin_data = get_file_data($this->plugin, array('Version' => 'Version'), false);
    $version     = $plugin_data['Version'];

    return $version;
  }

}

$speedy_search = new Speedy_Search();
$speedy_search->init();
