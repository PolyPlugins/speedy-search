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
    $this->maybe_load_advanced_search();
    $this->maybe_flush_rewrite_rules();
  }
  
  /**
   * Maybe load advanced search
   *
   * @return void
   */
  public function maybe_load_advanced_search() {
    $options = Utils::get_option('advanced');
    $enabled = isset($options['enabled']) ? $options['enabled'] : false;

    if ($enabled) {
      add_action('init', array($this, 'register_advanced_search_query'));
      add_filter('template_include', array($this, 'load_advanced_search_template'));
      add_filter('pre_get_document_title', array($this, 'update_title'), 9999);
      add_filter('the_title', array($this, 'update_title'), 9999);
    }
  }
  
  /**
   * Maybe flush rewrite rules
   *
   * @return void
   */
  public function maybe_flush_rewrite_rules() {
    $flush_rewrite_rules = get_option('speedy_search_flush_rewrite_rules_polyplugins');

    if ($flush_rewrite_rules) {
      flush_rewrite_rules(false);

      delete_option('speedy_search_flush_rewrite_rules_polyplugins');
    }
  }

  /**
  * Register advanced search query
  *
  * @return void
  */
  public function register_advanced_search_query() {
    add_rewrite_rule('^advanced-search/?$', 'index.php?advanced_search=1', 'top');

    add_filter('query_vars', function ($vars) {
      $vars[] = 'advanced_search';
      return $vars;
    });
  }

  /**
  * Load advanced search template
  *
  * @param  mixed $template
  * @return void
  */
  public function load_advanced_search_template($template) {
    if (get_query_var('advanced_search')) {
      return plugin_dir_path($this->plugin) . 'templates/snappy-search-advanced-search-form.php';
    }

    return $template;
  }
  
  /**
   * Update the title
   *
   * @param  string $title The Title
   * @return string $title The new title
   */
  public function update_title($title) {
    if (get_query_var('advanced_search')) {
      $title = 'Advanced Search';
    }

    return $title;
  }
  
}