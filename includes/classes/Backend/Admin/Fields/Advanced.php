<?php

namespace PolyPlugins\Speedy_Search\Backend\Admin\Fields;

use PolyPlugins\Speedy_Search\Utils;

class Advanced {

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
      'speedy_search_advanced_section_polyplugins',
      '',
      null,
      'speedy_search_advanced_polyplugins'
    );
  }
  
  /**
   * Add settings
   *
   * @return void
   */
  public function add_settings() {
		add_settings_field(
			'advanced_enabled',                             
			__('Enabled?', 'speedy-search'),
			array($this, 'advanced_enabled_render'),
			'speedy_search_advanced_polyplugins',
			'speedy_search_advanced_section_polyplugins'
		);

		add_settings_field(
			'advanced_title',                             
			__('Title', 'speedy-search'),
			array($this, 'advanced_title_render'),
			'speedy_search_advanced_polyplugins',
			'speedy_search_advanced_section_polyplugins'
		);

		add_settings_field(
			'advanced_placeholder',                             
			__('Placeholder', 'speedy-search'),
			array($this, 'advanced_placeholder_render'),
			'speedy_search_advanced_polyplugins',
			'speedy_search_advanced_section_polyplugins'
		);
  }
  
  /**
	 * Render Advanced Enabled Field
	 *
	 * @return void
	 */
	public function advanced_enabled_render() {
		$options = Utils::get_option('advanced');
    $option  = isset($options['enabled']) ? $options['enabled'] : false;
    ?>
    <div class="form-check form-switch">
      <input type="checkbox" name="speedy_search_settings_polyplugins[advanced][enabled]" class="form-check-input" role="switch" <?php checked(1, $option, true); ?> /> <?php esc_html_e('Yes', 'speedy-search'); ?>
    </div>
    <p><strong><?php esc_html_e('If enabled, pressing Enter will go to the page that you have the Advanced Snappy Search page template configured on instead of the default search, unless indexing is active.', 'speedy-search'); ?></strong></p>
    <?php
	}

  /**
	 * Render Advanced Title Field
	 *
	 * @return void
	 */
	public function advanced_title_render() {
		$options = Utils::get_option('advanced');
    $option  = isset($options['title']) ? $options['title'] : 'Advanced Search';
    ?>
    <input type="text" name="speedy_search_settings_polyplugins[advanced][title]" value="<?php echo esc_html($option); ?>" />
    <p><strong><?php esc_html_e('Set the page title of advanced search.', 'speedy-search'); ?></strong></p>
		<?php
	}

  /**
	 * Render Advanced Placeholder Field
	 *
	 * @return void
	 */
	public function advanced_placeholder_render() {
		$options = Utils::get_option('advanced');
    $option  = isset($options['placeholder']) ? $options['placeholder'] : 'Search...';
    ?>
    <input type="text" name="speedy_search_settings_polyplugins[advanced][placeholder]" value="<?php echo esc_html($option); ?>" />
    <p><strong><?php esc_html_e('Set the placeholder for the advanced search input.', 'speedy-search'); ?></strong></p>
		<?php
	}

}