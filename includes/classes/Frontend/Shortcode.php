<?php

namespace PolyPlugins\Speedy_Search\Frontend;

use PolyPlugins\Speedy_Search\Backend\DB;
use PolyPlugins\Speedy_Search\Utils;

class Shortcode {

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
    add_shortcode('speedy_search_polyplugins', array($this, 'snappy_search_shortcode_render'));
    add_shortcode('snappy_search_polyplugins', array($this, 'snappy_search_shortcode_render'));
    add_shortcode('snappy_search_mobile_polyplugins', array($this, 'snappy_search_mobile_shortcode_render'));
    add_shortcode('snappy_search_advanced_polyplugins', array($this, 'snappy_search_mobile_shortcode_render'));
  }

  public function snappy_search_shortcode_render($atts) {
    $atts = shortcode_atts(array(
      'placeholder' => 'Search...',
    ), $atts, 'snappy_search');

    ob_start();

    $template_file = locate_template('snappy-search-form.php');

    if (!empty($template_file)) {
      include $template_file;
    } else {
      include plugin_dir_path($this->plugin) . 'templates/snappy-search-form.php';
    }

    return ob_get_clean();
  }

  public function snappy_search_mobile_shortcode_render($atts) {
    $atts = shortcode_atts(array(
      'placeholder' => 'Search...',
    ), $atts, 'snappy_search');

    ob_start();

    $template_file = locate_template('templates/snappy-search-mobile-form.php');

    if (!empty($template_file)) {
      include $template_file;
    } else {
      include plugin_dir_path($this->plugin) . 'templates/snappy-search-mobile-form.php';
    }

    return ob_get_clean();
  }

  public function snappy_search_advanced_shortcode_render($atts) {
    $atts = shortcode_atts(array(
      'placeholder' => 'Search...',
    ), $atts, 'snappy_search');

    ob_start();

    $template_file = locate_template('snappy-search-advanced-form.php');

    if (!empty($template_file)) {
      include $template_file;
    } else {
      include plugin_dir_path($this->plugin) . 'templates/snappy-search-advanced-form.php';
    }

    return ob_get_clean();
  }
  
}