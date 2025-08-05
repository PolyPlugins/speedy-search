<?php

namespace PolyPlugins\Speedy_Search\Backend;

use PolyPlugins\Speedy_Search\Utils;

class Admin {

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
		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_init', array($this, 'settings_init'));
    add_filter('plugin_action_links_' . plugin_basename($this->plugin), array($this, 'add_setting_link'));
    
    $repo_enabled = Utils::get_option('repo_enabled');

    if ($repo_enabled) {
		  add_action('admin_menu', array($this, 'advanced_repo_search'));
    }
  }

	/**
	 * Add admin menu to backend
	 *
	 * @return void
	 */
	public function add_admin_menu() {
    add_action('admin_notices', array($this, 'maybe_show_indexing_notice'));
    add_action('admin_notices', array($this, 'maybe_show_missing_extensions_notice'));
		add_menu_page(__('Snappy Search', 'speedy-search'), __('Snappy Search', 'speedy-search'), 'manage_options', 'speedy-search', array($this, 'options_page'), 'dashicons-search');
	}
  
  /**
   * Maybe show indexing notice
   *
   * @return void
   */
  public function maybe_show_indexing_notice() {
    $enabled = Utils::get_option('enabled');

    if (!$enabled) {
      return;
    }

    $is_indexing = Utils::is_indexing();
    ?>
    <?php if ($is_indexing) : ?>
      <div class="notice notice-warning">
        <p><?php esc_html_e('Snappy Search is currently indexing.', 'speedy-search'); ?></p>
      </div>
    <?php endif; ?>
    <?php
  }
  
  /**
   * Maybe show missing extensions notice
   *
   * @return void
   */
  public function maybe_show_missing_extensions_notice() {
    $is_missing_extensions = Utils::is_missing_extensions();
    ?>
    <?php if ($is_missing_extensions) : ?>
      <div class="notice notice-error">
        <p><?php esc_html_e('Snappy Search requires the following extensions:', 'speedy-search'); ?></p>
        <?php foreach($is_missing_extensions as $missing_extension) : ?>
          <li><?php echo esc_html($missing_extension); ?></li>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php
  }
  
	/**
	 * Initialize Settings
	 *
	 * @return void
	 */
	public function settings_init() {
    // Register the setting page
    register_setting(
      'speedy_search_polyplugins',          // Option group
      'speedy_search_settings_polyplugins', // Option name
      array($this, 'sanitize')
    );

    add_settings_section(
      'speedy_search_general_section_polyplugins',
      '',
      null,
      'speedy_search_general_polyplugins'
    );

    add_settings_section(
      'speedy_search_popular_section_polyplugins',
      '',
      null,
      'speedy_search_popular_polyplugins'
    );

    add_settings_section(
      'speedy_search_posts_section_polyplugins',
      '',
      null,
      'speedy_search_posts_polyplugins'
    );

    add_settings_section(
      'speedy_search_pages_section_polyplugins',
      '',
      null,
      'speedy_search_pages_polyplugins'
    );

    add_settings_section(
      'speedy_search_products_section_polyplugins',
      '',
      null,
      'speedy_search_products_polyplugins'
    );

    add_settings_section(
      'speedy_search_downloads_section_polyplugins',
      '',
      null,
      'speedy_search_downloads_polyplugins'
    );

    add_settings_section(
      'speedy_search_advanced_section_polyplugins',
      '',
      null,
      'speedy_search_advanced_polyplugins'
    );

    add_settings_section(
      'speedy_search_repo_section_polyplugins',
      '',
      null,
      'speedy_search_repo_polyplugins'
    );
    
    // Add a setting under general section
		add_settings_field(
			'enabled',                                  // Setting Id
			__('Enabled?', 'speedy-search'),            // Setting Label
			array($this, 'enabled_render'),             // Setting callback
			'speedy_search_general_polyplugins',        // Setting page
			'speedy_search_general_section_polyplugins' // Setting section
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
    
		add_settings_field(
			'posts_enabled',
			__('Enabled?', 'speedy-search'),
			array($this, 'posts_enabled_render'),
			'speedy_search_posts_polyplugins',
			'speedy_search_posts_section_polyplugins'
		);

		add_settings_field(
			'posts_batch',
		  __('Batch', 'speedy-search'),
			array($this, 'posts_batch_render'),
			'speedy_search_posts_polyplugins',
			'speedy_search_posts_section_polyplugins'
		);
    
		add_settings_field(
			'result_limit',
			__('Result Limit', 'speedy-search'),
			array($this, 'result_limit_render'),
			'speedy_search_posts_polyplugins',
			'speedy_search_posts_section_polyplugins'
		);
    
		add_settings_field(
			'pages_enabled',
			__('Enabled?', 'speedy-search'),
			array($this, 'pages_enabled_render'),
			'speedy_search_pages_polyplugins',
			'speedy_search_pages_section_polyplugins'
		);

		add_settings_field(
			'pages_batch',
		  __('Batch', 'speedy-search'),
			array($this, 'pages_batch_render'),
			'speedy_search_pages_polyplugins',
			'speedy_search_pages_section_polyplugins'
		);
    
		add_settings_field(
			'pages_result_limit',
			__('Result Limit', 'speedy-search'),
			array($this, 'pages_result_limit_render'),
			'speedy_search_pages_polyplugins',
			'speedy_search_pages_section_polyplugins'
		);
    
		add_settings_field(
			'products_enabled',
			__('Enabled?', 'speedy-search'),
			array($this, 'products_enabled_render'),
			'speedy_search_products_polyplugins',
			'speedy_search_products_section_polyplugins'
		);

		add_settings_field(
			'products_batch',
		  __('Batch', 'speedy-search'),
			array($this, 'products_batch_render'),
			'speedy_search_products_polyplugins',
			'speedy_search_products_section_polyplugins'
		);
    
		add_settings_field(
			'products_result_limit',
			__('Result Limit', 'speedy-search'),
			array($this, 'products_result_limit_render'),
			'speedy_search_products_polyplugins',
			'speedy_search_products_section_polyplugins'
		);
    
		add_settings_field(
			'downloads_enabled',
			__('Enabled?', 'speedy-search'),
			array($this, 'downloads_enabled_render'),
			'speedy_search_downloads_polyplugins',
			'speedy_search_downloads_section_polyplugins'
		);

		add_settings_field(
			'downloads_batch',
		  __('Batch', 'speedy-search'),
			array($this, 'downloads_batch_render'),
			'speedy_search_downloads_polyplugins',
			'speedy_search_downloads_section_polyplugins'
		);
    
		add_settings_field(
			'downloads_result_limit',
			__('Result Limit', 'speedy-search'),
			array($this, 'downloads_result_limit_render'),
			'speedy_search_downloads_polyplugins',
			'speedy_search_downloads_section_polyplugins'
		);

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

		add_settings_field(
			'repo_enabled',                             
			__('Enabled?', 'speedy-search'),
			array($this, 'repo_enabled_render'),
			'speedy_search_repo_polyplugins',
			'speedy_search_repo_section_polyplugins'
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

  /**
	 * Render Enabled Field
	 *
	 * @return void
	 */
	public function posts_enabled_render() {
		$options = Utils::get_option('posts');
    $option  = isset($options['enabled']) ? $options['enabled'] : 1;
    ?>
    <div class="form-check form-switch">
      <input type="checkbox" name="speedy_search_settings_polyplugins[posts][enabled]" class="form-check-input" role="switch" <?php checked(1, $option, true); ?> /> <?php esc_html_e('Yes', 'speedy-search'); ?>
    </div>
    <p><strong><?php esc_html_e('Index and show posts in the search?', 'speedy-search'); ?></strong></p>
		<?php
	}

  /**
	 * Render Posts Batch Field
	 *
	 * @return void
	 */
	public function posts_batch_render() {
		$options = Utils::get_option('posts');
    $option  = isset($options['batch']) ? $options['batch'] : 20;
    ?>
    <input type="number" name="speedy_search_settings_polyplugins[posts][batch]" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('How many posts should be indexed per minute?', 'speedy-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Posts Batch Field
	 *
	 * @return void
	 */
	public function result_limit_render() {
		$options = Utils::get_option('posts');
    $option  = isset($options['result_limit']) ? $options['result_limit'] : 10;
    ?>
    <input type="number" name="speedy_search_settings_polyplugins[posts][result_limit]" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('How many posts would you like to show?', 'speedy-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Enabled Field
	 *
	 * @return void
	 */
	public function pages_enabled_render() {
		$options = Utils::get_option('pages');
    $option  = isset($options['enabled']) ? $options['enabled'] : 0;
    ?>
    <div class="form-check form-switch">
      <input type="checkbox" name="speedy_search_settings_polyplugins[pages][enabled]" class="form-check-input" role="switch" <?php checked(1, $option, true); ?> /> <?php esc_html_e('Yes', 'speedy-search'); ?>
    </div>
    <p><strong><?php esc_html_e('Index and show pages in the search?', 'speedy-search'); ?></strong></p>
		<?php
	}

  /**
	 * Render Pages Batch Field
	 *
	 * @return void
	 */
	public function pages_batch_render() {
		$options = Utils::get_option('pages');
    $option  = isset($options['batch']) ? $options['batch'] : 20;
    ?>
    <input type="number" name="speedy_search_settings_polyplugins[pages][batch]" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('How many pages should be indexed per minute?', 'speedy-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Pages Batch Field
	 *
	 * @return void
	 */
	public function pages_result_limit_render() {
		$options = Utils::get_option('pages');
    $option  = isset($options['result_limit']) ? $options['result_limit'] : 10;
    ?>
    <input type="number" name="speedy_search_settings_polyplugins[pages][result_limit]" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('How many pages would you like to show?', 'speedy-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Enabled Field
	 *
	 * @return void
	 */
	public function products_enabled_render() {
		$options = Utils::get_option('products');
    $option  = isset($options['enabled']) ? $options['enabled'] : 0;
    ?>
    <div class="form-check form-switch">
      <input type="checkbox" name="speedy_search_settings_polyplugins[products][enabled]" class="form-check-input" role="switch" <?php checked(1, $option, true); ?> /> <?php esc_html_e('Yes', 'speedy-search'); ?>
    </div>
    <p><strong><?php esc_html_e('Index and show products in the search?', 'speedy-search'); ?></strong></p>
		<?php
	}

  /**
	 * Render Products Batch Field
	 *
	 * @return void
	 */
	public function products_batch_render() {
		$options = Utils::get_option('products');
    $option  = isset($options['batch']) ? $options['batch'] : 20;
    ?>
    <input type="number" name="speedy_search_settings_polyplugins[products][batch]" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('How many products should be indexed per minute?', 'speedy-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Products Batch Field
	 *
	 * @return void
	 */
	public function products_result_limit_render() {
		$options = Utils::get_option('products');
    $option  = isset($options['result_limit']) ? $options['result_limit'] : 10;
    ?>
    <input type="number" name="speedy_search_settings_polyplugins[products][result_limit]" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('How many products would you like to show?', 'speedy-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Enabled Field
	 *
	 * @return void
	 */
	public function downloads_enabled_render() {
		$options = Utils::get_option('downloads');
    $option  = isset($options['enabled']) ? $options['enabled'] : 0;
    ?>
    <div class="form-check form-switch">
      <input type="checkbox" name="speedy_search_settings_polyplugins[downloads][enabled]" class="form-check-input" role="switch" <?php checked(1, $option, true); ?> /> <?php esc_html_e('Yes', 'speedy-search'); ?>
    </div>
    <p><strong><?php esc_html_e('Index and show downloads in the search?', 'speedy-search'); ?></strong></p>
		<?php
	}

  /**
	 * Render Downloads Batch Field
	 *
	 * @return void
	 */
	public function downloads_batch_render() {
		$options = Utils::get_option('downloads');
    $option  = isset($options['batch']) ? $options['batch'] : 20;
    ?>
    <input type="number" name="speedy_search_settings_polyplugins[downloads][batch]" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('How many downloads should be indexed per minute?', 'speedy-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Downloads Batch Field
	 *
	 * @return void
	 */
	public function downloads_result_limit_render() {
		$options = Utils::get_option('downloads');
    $option  = isset($options['result_limit']) ? $options['result_limit'] : 10;
    ?>
    <input type="number" name="speedy_search_settings_polyplugins[downloads][result_limit]" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('How many downloads would you like to show?', 'speedy-search'); ?></strong></p>
	  <?php
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
	
	/**
	 * Render options page
	 *
	 * @return void
	 */
	public function options_page() {
  ?>
    <form action='options.php' method='post'>
      <div class="bootstrap-wrapper">
        <div class="container">
          <div class="row">
            <div class="col-3"></div>
            <div class="col-6">
              <h1><?php esc_html_e('Snappy Search Settings', 'speedy-search'); ?></h1>
            </div>
            <div class="col-3"></div>
          </div>
          <div class="row">
            <div class="nav-links col-12 col-md-6 col-xl-3">
              <ul>
                <li>
                  <a href="javascript:void(0);" class="active" data-section="general">
                    <i class="bi bi-gear-fill"></i>
                    <?php esc_html_e('General', 'speedy-search'); ?>
                  </a>
                </li>
                <li>
                  <a href="javascript:void(0);" data-section="popular">
                    <i class="bi bi-fire"></i>
                    <?php esc_html_e('Popular', 'speedy-search'); ?>
                  </a>
                </li>
                <li>
                  <a href="javascript:void(0);" data-section="posts">
                    <i class="bi bi-pencil"></i>
                    <?php esc_html_e('Posts', 'speedy-search'); ?>
                  </a>
                </li>
                <li>
                  <a href="javascript:void(0);" data-section="pages">
                    <i class="bi bi-file-earmark-fill"></i>
                    <?php esc_html_e('Pages', 'speedy-search'); ?>
                  </a>
                </li>
                <?php if (class_exists('WooCommerce')) : ?>
                  <li>
                    <a href="javascript:void(0);" data-section="products">
                      <i class="bi bi-bag-fill"></i>
                      <?php esc_html_e('Products', 'speedy-search'); ?>
                    </a>
                  </li>
                <?php endif; ?>
                <?php if (class_exists('Easy_Digital_Downloads')) : ?>
                  <li>
                    <a href="javascript:void(0);" data-section="downloads">
                      <i class="bi bi-file-earmark-arrow-down-fill"></i>
                      <?php esc_html_e('Downloads', 'speedy-search'); ?>
                    </a>
                  </li>
                <?php endif; ?>
                <li>
                  <a href="javascript:void(0);" data-section="advanced">
                    <i class="bi bi-funnel-fill"></i>
                    <?php esc_html_e('Advanced', 'speedy-search'); ?>
                  </a>
                </li>
                <li>
                  <a href="javascript:void(0);" data-section="repo">
                    <i class="bi bi-plug-fill"></i>
                    <?php esc_html_e('Repo', 'speedy-search'); ?>
                  </a>
                </li>
                <li>
                  <a href="javascript:void(0);" data-section="reindex">
                    <i class="bi bi-database-fill"></i>
                    <?php esc_html_e('Reindex', 'speedy-search'); ?>
                  </a>
                </li>
              </ul>
            </div>
            <div class="tabs col-12 col-md-6 col-xl-6">
              <div class="tab general">
                <?php
                do_settings_sections('speedy_search_general_polyplugins');
                ?>
              </div>

              <div class="tab popular" style="display: none;">
                <?php
                do_settings_sections('speedy_search_popular_polyplugins');
                ?>
              </div>

              <div class="tab posts" style="display: none;">
                <?php
                do_settings_sections('speedy_search_posts_polyplugins');
                ?>
              </div>

              <div class="tab pages" style="display: none;">
                <?php
                do_settings_sections('speedy_search_pages_polyplugins');
                ?>
              </div>

              <?php if (class_exists('WooCommerce')) : ?>
                <div class="tab products" style="display: none;">
                  <?php
                  do_settings_sections('speedy_search_products_polyplugins');
                  ?>
                </div>
              <?php endif; ?>

              <?php if (class_exists('Easy_Digital_Downloads')) : ?>
                <div class="tab downloads" style="display: none;">
                  <?php
                  do_settings_sections('speedy_search_downloads_polyplugins');
                  ?>
                </div>
              <?php endif; ?>

              <div class="tab advanced" style="display: none;">
                <div class="warning">
                  <?php esc_html_e('Due to the layout differences of various themes, this more than likely will require a developer to add and customize the snappy-search-advanced-search-form.php template in your theme.', 'speedy-search'); ?> If you need a developer you can <a href="https://www.polyplugins.com/contact/" target="_blank">hire us</a> to help.
                </div>
                <?php
                do_settings_sections('speedy_search_advanced_polyplugins');
                ?>
              </div>

              <div class="tab repo" style="display: none;">
                <?php
                do_settings_sections('speedy_search_repo_polyplugins');
                ?>
              </div>
            
              <?php
              settings_fields('speedy_search_polyplugins');
              submit_button();
              ?>
              
            </div>

            <div class="ctas col-12 col-md-12 col-xl-3">
              <div class="cta">
                <h2 style="color: #fff;">Something Not Working?</h2>
                <p>We pride ourselves on quality, so if something isn't working or you have a suggestion, feel free to call or email us. We're based out of Tennessee in the USA.
                <p><a href="tel:+14232818591" class="button button-primary" style="text-decoration: none; color: #fff; font-weight: 700; text-transform: uppercase; background-color: #333; border-color: #333;" target="_blank">Call Us</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://www.polyplugins.com/contact/" class="button button-primary" style="text-decoration: none; color: #fff; font-weight: 700; text-transform: uppercase; background-color: #333; border-color: #333;" target="_blank">Email Us</a></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
  <?php
  }

  /**
   * Sanitize Options
   *
   * @param  array $input Array of option inputs
   * @return array $sanitary_values Array of sanitized options
   */
  public function sanitize($input) {
		$sanitary_values = array();

    if (isset($input['enabled']) && $input['enabled']) {
      $sanitary_values['enabled'] = $input['enabled'] === 'on' ? true : false;
    } else {
      $sanitary_values['enabled'] = false;
    }

    if (isset($input['database_type'])) {
      $allowed_types         = array('mysql', 'sqlite');
      $database_type         = sanitize_text_field($input['database_type']);
		  $current_database_type = Utils::get_option('database_type') ?: 'mysql';

      if (in_array($database_type, $allowed_types, true)) {
        $sanitary_values['database_type'] = $database_type;
      } else {
        $sanitary_values['database_type'] = 'mysql';
      }

      if ($current_database_type !== $database_type) {
        Utils::reindex();
      }
    }

    if (isset($input['characters']) && is_numeric($input['characters'])) {
			$sanitary_values['characters'] = sanitize_text_field($input['characters']);
		}

    if (isset($input['max_characters']) && is_numeric($input['max_characters'])) {
			$sanitary_values['max_characters'] = sanitize_text_field($input['max_characters']);
		}

    if (isset($input['typing_delay']) && is_numeric($input['typing_delay'])) {
			$sanitary_values['typing_delay'] = sanitize_text_field($input['typing_delay']);
		}

    if (isset($input['selector']) && $input['selector']) {
			$sanitary_values['selector'] = sanitize_text_field($input['selector']);
		}

    if (isset($input['popular']['enabled']) && $input['popular']['enabled']) {
      $sanitary_values['popular']['enabled'] = $input['popular']['enabled'] === 'on' ? true : false;
    } else {
      $sanitary_values['popular']['enabled'] = false;
    }

    if (isset($input['popular']['limit']) && is_numeric($input['popular']['limit'])) {
			$sanitary_values['popular']['limit'] = sanitize_text_field($input['popular']['limit']);
		}

    if (isset($input['popular']['days']) && is_numeric($input['popular']['days'])) {
			$sanitary_values['popular']['days'] = sanitize_text_field($input['popular']['days']);
		}

    if (isset($input['popular']['delay']) && is_numeric($input['popular']['delay'])) {
			$sanitary_values['popular']['delay'] = sanitize_text_field($input['popular']['delay']);
		}

    if (isset($input['popular']['characters']) && is_numeric($input['popular']['characters'])) {
			$sanitary_values['popular']['characters'] = sanitize_text_field($input['popular']['characters']);
		}

    if (isset($input['popular']['result_count']) && is_numeric($input['popular']['result_count'])) {
			$sanitary_values['popular']['result_count'] = sanitize_text_field($input['popular']['result_count']);
		}

    if (isset($input['popular']['blacklist']) && $input['popular']['blacklist']) {
			$sanitary_values['popular']['blacklist'] = sanitize_text_field($input['popular']['blacklist']);
		}

    if (isset($input['posts']['enabled']) && $input['posts']['enabled']) {
      $sanitary_values['posts']['enabled'] = $input['posts']['enabled'] === 'on' ? true : false;
    } else {
      $sanitary_values['posts']['enabled'] = false;
    }

    if (isset($input['posts']['batch']) && is_numeric($input['posts']['batch'])) {
			$sanitary_values['posts']['batch'] = sanitize_text_field($input['posts']['batch']);
		}

    if (isset($input['posts']['result_limit']) && is_numeric($input['posts']['result_limit'])) {
			$sanitary_values['posts']['result_limit'] = sanitize_text_field($input['posts']['result_limit']);
		}

    if (isset($input['pages']['enabled']) && $input['pages']['enabled']) {
      $sanitary_values['pages']['enabled'] = $input['pages']['enabled'] === 'on' ? true : false;
    } else {
      $sanitary_values['pages']['enabled'] = false;
    }

    if (isset($input['pages']['batch']) && is_numeric($input['pages']['batch'])) {
			$sanitary_values['pages']['batch'] = sanitize_text_field($input['pages']['batch']);
		}

    if (isset($input['pages']['result_limit']) && is_numeric($input['pages']['result_limit'])) {
			$sanitary_values['pages']['result_limit'] = sanitize_text_field($input['pages']['result_limit']);
		}

    if (isset($input['products']['enabled']) && $input['products']['enabled']) {
      $sanitary_values['products']['enabled'] = $input['products']['enabled'] === 'on' ? true : false;
    } else {
      $sanitary_values['products']['enabled'] = false;
    }

    if (isset($input['products']['batch']) && is_numeric($input['products']['batch'])) {
			$sanitary_values['products']['batch'] = sanitize_text_field($input['products']['batch']);
		}

    if (isset($input['products']['result_limit']) && is_numeric($input['products']['result_limit'])) {
			$sanitary_values['products']['result_limit'] = sanitize_text_field($input['products']['result_limit']);
		}

    if (isset($input['downloads']['enabled']) && $input['downloads']['enabled']) {
      $sanitary_values['downloads']['enabled'] = $input['downloads']['enabled'] === 'on' ? true : false;
    } else {
      $sanitary_values['downloads']['enabled'] = false;
    }

    if (isset($input['downloads']['batch']) && is_numeric($input['downloads']['batch'])) {
			$sanitary_values['downloads']['batch'] = sanitize_text_field($input['downloads']['batch']);
		}

    if (isset($input['downloads']['result_limit']) && is_numeric($input['downloads']['result_limit'])) {
			$sanitary_values['downloads']['result_limit'] = sanitize_text_field($input['downloads']['result_limit']);
		}

    if (isset($input['advanced']['enabled']) && $input['advanced']['enabled']) {
      $sanitary_values['advanced']['enabled'] = $input['advanced']['enabled'] === 'on' ? true : false;
    } else {
      $sanitary_values['advanced']['enabled'] = false;
    }

    if (isset($input['advanced']['title']) && $input['advanced']['title']) {
			$sanitary_values['advanced']['title'] = sanitize_text_field($input['advanced']['title']);
		}

    if (isset($input['advanced']['placeholder']) && $input['advanced']['placeholder']) {
			$sanitary_values['advanced']['placeholder'] = sanitize_text_field($input['advanced']['placeholder']);
		}

    if (isset($input['repo_enabled']) && $input['repo_enabled']) {
      $sanitary_values['repo_enabled'] = $input['repo_enabled'] === 'on' ? true : false;
    } else {
      $sanitary_values['repo_enabled'] = false;
    }

    return $sanitary_values;
  }
  
  /**
   * Add setting link
   *
   * @return void
   */
  public function add_setting_link($links) {
    $settings_link = '<a href="options-general.php?page=speedy-search">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
  }

  public function advanced_repo_search() {
    add_submenu_page(
      'plugins.php',
      'Advanced Search',
      'Advanced Search',
      'manage_options',
      'repo-advanced-search',
      array($this, 'repo_advanced_search_page')
    );
  }

  public function repo_advanced_search_page() {
    include plugin_dir_path($this->plugin) . 'templates/repo-advanced-search.php';
  }


}