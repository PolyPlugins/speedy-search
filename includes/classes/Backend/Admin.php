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
		add_submenu_page('options-general.php', __('Speedy Search', 'speedy-search'), __('Speedy Search', 'speedy-search'), 'manage_options', 'speedy-search', array($this, 'options_page'));
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

    $posts_index          = Utils::get_index('posts');
    $is_indexing_complete = isset($posts_index['complete']) ? true : false;
    ?>
    <?php if (!$is_indexing_complete) : ?>
      <div class="notice notice-warning">
        <p><?php esc_html_e('Speedy Search is currently indexing.', 'speedy-search'); ?></p>
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
        <p><?php esc_html_e('Speedy Search requires the following extensions:', 'speedy-search'); ?></p>
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
      'speedy_search_posts_section_polyplugins',
      '',
      null,
      'speedy_search_posts_polyplugins'
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

		add_settings_field(
			'selector',
			__('Selector', 'speedy-search'),
			array($this, 'selector_render'),
			'speedy_search_general_polyplugins',
			'speedy_search_general_section_polyplugins'
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
	 * Render Posts Batch Field
	 *
	 * @return void
	 */
	public function selector_render() {
		$option = Utils::get_option('selector');
    ?>
    <input type="text" name="speedy_search_settings_polyplugins[selector]" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('Enter your selector that you want to add the instant search to. Ex: #search', 'speedy-search'); ?></strong></p>
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
              <h1><?php esc_html_e('Speedy Search Settings', 'speedy-search'); ?></h1>
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
                  <a href="javascript:void(0);" data-section="posts">
                    <i class="bi bi-pencil"></i>
                    <?php esc_html_e('Posts', 'speedy-search'); ?>
                  </a>
                </li>
                <li>
                  <a href="javascript:void(0);" data-section="repo">
                    <i class="bi bi-plug-fill"></i>
                    <?php esc_html_e('Repo', 'speedy-search'); ?>
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

              <div class="tab posts" style="display: none;">
                <?php
                do_settings_sections('speedy_search_posts_polyplugins');
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

    if (isset($input['selector']) && $input['selector']) {
			$sanitary_values['selector'] = sanitize_text_field($input['selector']);
		}

    if (isset($input['posts']['batch']) && is_numeric($input['posts']['batch'])) {
			$sanitary_values['posts']['batch'] = sanitize_text_field($input['posts']['batch']);
		}

    if (isset($input['posts']['result_limit']) && is_numeric($input['posts']['result_limit'])) {
			$sanitary_values['posts']['result_limit'] = sanitize_text_field($input['posts']['result_limit']);
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