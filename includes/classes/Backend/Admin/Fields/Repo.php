<?php

namespace PolyPlugins\Speedy_Search\Backend\Admin\Fields;

use PolyPlugins\Speedy_Search\Utils;

class Repo {

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
		$this->add_section();
    $this->add_settings();
  }
  
  /**
   * Add section
   *
   * @return void
   */
  public function add_section() {
    add_settings_section(
      'speedy_search_repo_section_polyplugins',
      '',
      null,
      'speedy_search_repo_polyplugins'
    );
  }
  
  /**
   * Add settings
   *
   * @return void
   */
  public function add_settings() {
		add_settings_field(
			'repo_enabled',                             
			__('Enabled?', 'speedy-search'),
			array($this, 'repo_enabled_render'),
			'speedy_search_repo_polyplugins',
			'speedy_search_repo_section_polyplugins'
		);
  }
  
  /**
	 * Render Repo Enabled Field
	 *
	 * @return void
	 */
	public function repo_enabled_render() {
		$option = Utils::get_option('repo_enabled');
    ?>
    <div class="form-check form-switch">
      <input type="checkbox" name="speedy_search_settings_polyplugins[repo_enabled]" class="form-check-input" role="switch" <?php checked(1, $option, true); ?> /> <?php esc_html_e('Yes', 'speedy-search'); ?>
    </div>
    <p>This adds an advanced search under the plugin menu. This does collect data on your searches and IP address. Please see our <a href="https://www.polyplugins.com/privacy-policy/" target="_blank">Privacy Policy</a> for more information.</p>
		<?php
	}

}