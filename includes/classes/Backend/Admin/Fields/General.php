<?php

namespace PolyPlugins\Speedy_Search\Backend\Admin\Fields;

use PolyPlugins\Speedy_Search\Utils;

class General {

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
      'speedy_search_general_section_polyplugins',
      '',
      null,
      'speedy_search_general_polyplugins'
    );
  }
  
  /**
   * Add settings
   *
   * @return void
   */
  public function add_settings() {
    // Add a setting under general section
		add_settings_field(
			'enabled',                                  // Setting Id
			__('Enabled?', 'speedy-search'),            // Setting Label
			array($this, 'enabled_render'),             // Setting callback
			'speedy_search_general_polyplugins',        // Setting page
			'speedy_search_general_section_polyplugins' // Setting section
		);

    add_settings_field(
      'default_result_type',
      __('Default Result Type', 'speedy-search'),
      array($this, 'default_result_type_render'),
      'speedy_search_general_polyplugins',
      'speedy_search_general_section_polyplugins'
    );

    $database_type = Utils::get_option('database_type') ?: 'mysql';

    if ($database_type !== 'mysql') {
      add_settings_field(
        'database_type',
        __('Database Type', 'speedy-search'),
        array($this, 'database_type_render'),
        'speedy_search_general_polyplugins',
        'speedy_search_general_section_polyplugins'
      );
    }

		add_settings_field(
			'characters',
		  __('Characters', 'speedy-search'),
			array($this, 'characters_render'),
			'speedy_search_general_polyplugins',
			'speedy_search_general_section_polyplugins'
		);

		add_settings_field(
			'max_characters',
		  __('Max Characters', 'speedy-search'),
			array($this, 'max_characters_render'),
			'speedy_search_general_polyplugins',
			'speedy_search_general_section_polyplugins'
		);

		add_settings_field(
			'typing_delay',
		  __('Typing Delay', 'speedy-search'),
			array($this, 'typing_delay_render'),
			'speedy_search_general_polyplugins',
			'speedy_search_general_section_polyplugins'
		);

		add_settings_field(
			'selector',
			__('Selector', 'speedy-search'),
			array($this, 'selector_render'),
			'speedy_search_general_polyplugins',
			'speedy_search_general_section_polyplugins'
		);
  }

  /**
	 * Render Enabled Field
	 *
	 * @return void
	 */
	public function enabled_render() {
		$option = Utils::get_option('enabled'); // Get enabled option value
    ?>
    <div class="form-check form-switch">
      <input type="checkbox" name="speedy_search_settings_polyplugins[enabled]" class="form-check-input" role="switch" <?php checked(1, $option, true); ?> /> <?php esc_html_e('Yes', 'speedy-search'); ?>
    </div>
		<?php
	}

  /**
	 * Render Database Type Field
	 *
	 * @return void
	 */
	public function default_result_type_render() {
    $allowed_types = Utils::get_allowed_post_types();
		$option = Utils::get_option('default_result_type') ?: $allowed_types[0];
    ?>
    <select name="speedy_search_settings_polyplugins[default_result_type]">
      <?php foreach ($allowed_types as $key => $label) : ?>
        <option value="<?php echo esc_html($key); ?>" <?php selected($option, $key); ?>><?php echo esc_html($label); ?></option>
      <?php endforeach; ?>
    </select>
    <p><strong><?php esc_html_e("Select the default that is displayed first in results.", 'speedy-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Database Type Field
	 *
	 * @return void
	 */
	public function database_type_render() {
		$option = Utils::get_option('database_type') ?: 'mysql';
    ?>
    <select name="speedy_search_settings_polyplugins[database_type]">
      <option value="mysql" <?php selected($option, 'mysql'); ?>>MySQL</option>
      <option value="sqlite" <?php selected($option, 'sqlite'); ?>>SQLite</option>
    </select>
    <p><strong><?php esc_html_e("If your server runs MySQL you can store the index inside your existing database, otherwise its stored on your filesystem.", 'speedy-search'); ?><br /><br /><?php esc_html_e("Note: Changing this setting triggers a reindex.", 'speedy-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Characters Field
	 *
	 * @return void
	 */
	public function characters_render() {
		$option = Utils::get_option('characters') ?: 4;
    ?>
    <input type="number" name="speedy_search_settings_polyplugins[characters]" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('How many characters to trigger Snappy Search?', 'speedy-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Characters Field
	 *
	 * @return void
	 */
	public function max_characters_render() {
		$option = Utils::get_option('max_characters') ?: 100;
    ?>
    <input type="number" name="speedy_search_settings_polyplugins[max_characters]" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('Maximum number of characters allowed to be searched?', 'speedy-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Typing Delay Field
	 *
	 * @return void
	 */
	public function typing_delay_render() {
		$option = Utils::get_option('typing_delay') ?: 300;
    ?>
    <input type="number" name="speedy_search_settings_polyplugins[typing_delay]" id="typing_delay" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('How many milliseconds between inputs until a search is fired?', 'speedy-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Posts Batch Field
	 *
	 * @return void
	 */
	public function selector_render() {
		$option = Utils::get_option('selector');
    ?>
    <input type="text" name="speedy_search_settings_polyplugins[selector]" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('Enter your selector that you want to add the instant search to. Ex: #search', 'speedy-search'); ?><br /><br /><?php esc_html_e('Leave blank if you are using the [snappy_search_polyplugins] shortcode.', 'speedy-search'); ?></strong></p>
	  <?php
	}

}