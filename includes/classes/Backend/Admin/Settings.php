<?php

namespace PolyPlugins\Speedy_Search\Backend\Admin;

use PolyPlugins\Speedy_Search\Utils;

class Settings {

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
   * Array of setting field instances
   *
   * @var array
   */
  private $field_instances = array();
  
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
		add_action('admin_init', array($this, 'register_settings'));
		add_action('admin_init', array($this, 'load_setting_fields'));
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
	 * Register Settings
	 *
	 * @return void
	 */
	public function register_settings() {
    // Register the setting page
    register_setting(
      'speedy_search_polyplugins',          // Option group
      'speedy_search_settings_polyplugins', // Option name
      array($this, 'sanitize')
    );
	}
  
	/**
	 * Load setting fields
	 *
	 * @return void
	 */
	public function load_setting_fields() {
    $field_classes = array(
      'repo'      => '\PolyPlugins\Speedy_Search\Backend\Admin\Fields\Repo',
      'advanced'  => '\PolyPlugins\Speedy_Search\Backend\Admin\Fields\Advanced',
      'downloads' => '\PolyPlugins\Speedy_Search\Backend\Admin\Fields\Downloads',
      'products'  => '\PolyPlugins\Speedy_Search\Backend\Admin\Fields\Products',
      'pages'     => '\PolyPlugins\Speedy_Search\Backend\Admin\Fields\Pages',
      'posts'     => '\PolyPlugins\Speedy_Search\Backend\Admin\Fields\Posts',
      'popular'   => '\PolyPlugins\Speedy_Search\Backend\Admin\Fields\Popular',
      'general'   => '\PolyPlugins\Speedy_Search\Backend\Admin\Fields\General',
    );

    foreach ($field_classes as $key => $class) {
      if (class_exists($class)) {
        $instance = new $class($this->plugin, $this->version, $this->plugin_dir_url);
        $instance->init();
        
        $this->field_instances[$key] = $instance;
      }
    }
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