<?php

namespace PolyPlugins\Speedy_Search\Backend\Admin\Fields;

use PolyPlugins\Speedy_Search\Utils;

class Popular {

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
      'speedy_search_popular_section_polyplugins',
      '',
      null,
      'speedy_search_popular_polyplugins'
    );
  }
  
  /**
   * Add settings
   *
   * @return void
   */
  public function add_settings() {
		add_settings_field(
			'popular_enabled',
			__('Enabled?', 'speedy-search'),
			array($this, 'popular_enabled_render'),
			'speedy_search_popular_polyplugins',
			'speedy_search_popular_section_polyplugins'
		);
    
		add_settings_field(
			'popular_limit',
			__('Limit', 'speedy-search'),
			array($this, 'popular_limit_render'),
			'speedy_search_popular_polyplugins',
			'speedy_search_popular_section_polyplugins'
		);
    
		add_settings_field(
			'popular_days',
			__('Days', 'speedy-search'),
			array($this, 'popular_days_render'),
			'speedy_search_popular_polyplugins',
			'speedy_search_popular_section_polyplugins'
		);
    
		add_settings_field(
			'popular_tracking_delay',
			__('Tracking Delay', 'speedy-search'),
			array($this, 'popular_tracking_delay_render'),
			'speedy_search_popular_polyplugins',
			'speedy_search_popular_section_polyplugins'
		);
    
		add_settings_field(
			'popular_characters',
			__('Characters', 'speedy-search'),
			array($this, 'popular_characters_render'),
			'speedy_search_popular_polyplugins',
			'speedy_search_popular_section_polyplugins'
		);
    
		add_settings_field(
			'popular_result_count',
			__('Result Count', 'speedy-search'),
			array($this, 'popular_result_count_render'),
			'speedy_search_popular_polyplugins',
			'speedy_search_popular_section_polyplugins'
		);
    
		add_settings_field(
			'popular_blacklist',
			__('Blacklisted Words', 'speedy-search'),
			array($this, 'popular_blacklist_render'),
			'speedy_search_popular_polyplugins',
			'speedy_search_popular_section_polyplugins'
		);
  }
  
  /**
	 * Render Enabled Field
	 *
	 * @return void
	 */
	public function popular_enabled_render() {
		$options = Utils::get_option('popular');
    $option  = isset($options['enabled']) ? $options['enabled'] : 0;
    ?>
    <div class="form-check form-switch">
      <input type="checkbox" name="speedy_search_settings_polyplugins[popular][enabled]" class="form-check-input" role="switch" <?php checked(1, $option, true); ?> /> <?php esc_html_e('Yes', 'speedy-search'); ?>
    </div>
    <p><strong><?php esc_html_e('Enabling this will track searches and display popular search terms. This will track what users are searching, but it is not tied to individual users. Before you enable, please make sure you are in compliance with data protection regulations.', 'speedy-search'); ?></strong></p>
		<?php
	}

  /**
	 * Render Limit
	 *
	 * @return void
	 */
	public function popular_limit_render() {
		$options = Utils::get_option('popular');
    $option  = isset($options['limit']) && is_numeric($options['limit']) ? $options['limit'] : 5;
    ?>
    <input type="number" name="speedy_search_settings_polyplugins[popular][limit]" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('Number of popular search terms to show.', 'speedy-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Popular Days
	 *
	 * @return void
	 */
	public function popular_days_render() {
		$options = Utils::get_option('popular');
    $option  = isset($options['days']) && is_numeric($options['days']) ? $options['days'] : 30;
    ?>
    <input type="number" name="speedy_search_settings_polyplugins[popular][days]" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('The number of days of search term history to look through for popular search terms. Note: We run a background worker that will cleanup any search term history beyond the number of days you set.', 'speedy-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Popular Tracking Delay
	 *
	 * @return void
	 */
	public function popular_tracking_delay_render() {
		$options = Utils::get_option('popular');
    $option  = isset($options['delay']) && is_numeric($options['delay']) ? $options['delay'] : 3000;
    ?>
    <input type="number" name="speedy_search_settings_polyplugins[popular][delay]" id="tracking_delay" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('How many milliseconds after a user is done typing, until the search is tracked?', 'speedy-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Characters
	 *
	 * @return void
	 */
	public function popular_characters_render() {
		$options = Utils::get_option('popular');
    $option  = isset($options['characters']) && is_numeric($options['characters']) ? $options['characters'] : 3;
    ?>
    <input type="number" name="speedy_search_settings_polyplugins[popular][characters]" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('Number of characters required for the search term to be tracked.', 'speedy-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Popular Result Count
	 *
	 * @return void
	 */
	public function popular_result_count_render() {
		$options = Utils::get_option('popular');
    $option  = isset($options['result_count']) && is_numeric($options['result_count']) ? $options['result_count'] : 10;
    ?>
    <input type="number" name="speedy_search_settings_polyplugins[popular][result_count]" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('The number of found results for a search term to be considered popular. Set higher than 0 to prevent search terms with no results from showing as a popular term.', 'speedy-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Blacklist Field
	 *
	 * @return void
	 */
	public function popular_blacklist_render() {
		$options = Utils::get_option('popular');
    $option  = isset($options['blacklist']) ? $options['blacklist'] : '';
    ?>
    <textarea type="text" name="speedy_search_settings_polyplugins[popular][blacklist]" rows="5"><?php echo esc_html($option); ?></textarea>
    <p><strong><?php esc_html_e("By default, Snappy Search only shows popular search terms that return results. To block specific terms from appearing, enter them here as a comma separated list.", 'speedy-search'); ?></p>
	  <?php
	}

}