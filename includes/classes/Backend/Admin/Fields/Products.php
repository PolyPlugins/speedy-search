<?php

namespace PolyPlugins\Speedy_Search\Backend\Admin\Fields;

use PolyPlugins\Speedy_Search\Utils;

class Products {

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
      'speedy_search_products_section_polyplugins',
      '',
      null,
      'speedy_search_products_polyplugins'
    );
  }
  
  /**
   * Add settings
   *
   * @return void
   */
  public function add_settings() {
		add_settings_field(
			'products_enabled',
			__('Enabled?', 'speedy-search'),
			array($this, 'products_enabled_render'),
			'speedy_search_products_polyplugins',
			'speedy_search_products_section_polyplugins'
		);

		add_settings_field(
			'products_tab_enabled',
			__('Tab Enabled?', 'speedy-search'),
			array($this, 'products_tab_enabled_render'),
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
	 * Render Enabled Field
	 *
	 * @return void
	 */
	public function products_tab_enabled_render() {
		$options = Utils::get_option('products');
    $option  = isset($options['tab_enabled']) ? $options['tab_enabled'] : 1;
    ?>
    <div class="form-check form-switch">
      <input type="checkbox" name="speedy_search_settings_polyplugins[products][tab_enabled]" class="form-check-input" role="switch" <?php checked(1, $option, true); ?> /> <?php esc_html_e('Yes', 'speedy-search'); ?>
    </div>
    <p><strong><?php esc_html_e('Show the products tab on non advanced search.', 'speedy-search'); ?></strong></p>
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

}