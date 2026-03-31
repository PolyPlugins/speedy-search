<?php

namespace PolyPlugins\Speedy_Search\Backend\Admin\Fields;

use PolyPlugins\Speedy_Search\Utils;

class Filters {

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
      'speedy_search_filters_section_polyplugins',
      '',
      null,
      'speedy_search_filters_polyplugins'
    );
  }
  
  /**
   * Add settings
   *
   * @return void
   */
  public function add_settings() {
		add_settings_field(
			'shortcode_filters_enabled',
			__('Shortcode Product Filters?', 'speedy-search'),
			array($this, 'shortcode_filters_enabled_render'),
			'speedy_search_filters_polyplugins',
			'speedy_search_filters_section_polyplugins'
		);

		add_settings_field(
			'selector_filters_enabled',
			__('Selector Product Filters?', 'speedy-search'),
			array($this, 'selector_filters_enabled_render'),
			'speedy_search_filters_polyplugins',
			'speedy_search_filters_section_polyplugins'
		);

		add_settings_field(
			'advanced_filters_enabled',
			__('Advanced Product Filters?', 'speedy-search'),
			array($this, 'advanced_filters_enabled_render'),
			'speedy_search_filters_polyplugins',
			'speedy_search_filters_section_polyplugins'
		);
  }

  /**
	 * Render Shortcode Filters Enabled Field
	 *
	 * @return void
	 */
	public function shortcode_filters_enabled_render() {
		$option = Utils::get_option('shortcode_filters_enabled');
    ?>
    <div class="form-check form-switch">
      <input type="checkbox" name="speedy_search_settings_polyplugins[shortcode_filters_enabled]" class="form-check-input" role="switch" <?php checked(1, $option, true); ?> /> <?php esc_html_e('Yes', 'speedy-search'); ?>
    </div>
    <p><strong><?php esc_html_e('Enable product filters (rating and price) on shortcode results.', 'speedy-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Selector Filters Enabled Field
	 *
	 * @return void
	 */
	public function selector_filters_enabled_render() {
		$option = Utils::get_option('selector_filters_enabled');
    ?>
    <div class="form-check form-switch">
      <input type="checkbox" name="speedy_search_settings_polyplugins[selector_filters_enabled]" class="form-check-input" role="switch" <?php checked(1, $option, true); ?> /> <?php esc_html_e('Yes', 'speedy-search'); ?>
    </div>
    <p><strong><?php esc_html_e('Enable product filters (rating and price) on selector results.', 'speedy-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Advanced Product Filters Enabled Field
	 *
	 * @return void
	 */
	public function advanced_filters_enabled_render() {
		$options = Utils::get_option('advanced');
    $option  = isset($options['filters_enabled']) ? $options['filters_enabled'] : false;
    ?>
    <div class="form-check form-switch">
      <input type="checkbox" name="speedy_search_settings_polyplugins[advanced][filters_enabled]" class="form-check-input" role="switch" <?php checked(1, $option, true); ?> /> <?php esc_html_e('Yes', 'speedy-search'); ?>
    </div>
    <p><strong><?php esc_html_e('Enable product filters (rating and price) on advanced results.', 'speedy-search'); ?></strong></p>
		<?php
	}

}
