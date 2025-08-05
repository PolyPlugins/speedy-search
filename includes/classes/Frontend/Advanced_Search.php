<?php

namespace PolyPlugins\Speedy_Search\Frontend;

use PolyPlugins\Speedy_Search\Utils;

class Advanced_Search {

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
    add_filter('theme_page_templates', array($this, 'register_advanced_template'));
    add_filter('template_include', array($this, 'load_advanced_template'));
  }
  
  /**
   * Register advanced template
   *
   * @return void
   */
  public function register_advanced_template($templates) {
    $templates['snappy-search-advanced-search-form.php'] = __('Advanced Snappy Search', 'speedy-search');

    return $templates;
  }

  /**
  * Load advanced template
  *
  * @param  mixed $template
  * @return void
  */
  public function load_advanced_template($template) {
    if (is_page()) {
      $page_template = get_page_template_slug();

      if ($page_template === 'snappy-search-advanced-search-form.php') {
        // Check if the theme has the template first
        $theme_template = locate_template('snappy-search-advanced-search-form.php');

        if (!empty($theme_template)) {
          return $theme_template;
        }

        // Fallback to plugin template
        $plugin_template = plugin_dir_path($this->plugin) . 'templates/snappy-search-advanced-search-form.php';

        if (file_exists($plugin_template)) {
          return $plugin_template;
        }
      }
    }

    return $template;
  }
  
}