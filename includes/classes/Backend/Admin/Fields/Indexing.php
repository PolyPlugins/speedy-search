<?php

namespace PolyPlugins\Speedy_Search\Backend\Admin\Fields;

use PolyPlugins\Speedy_Search\Utils;

if (!defined('ABSPATH')) exit;

class Indexing {

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
      'speedy_search_indexing_section_polyplugins',
      '',
      null,
      'speedy_search_indexing_polyplugins'
    );
  }

  /**
   * Add settings
   *
   * @return void
   */
  public function add_settings() {
		add_settings_field(
			'indexing_title',
			__('Index titles?', 'speedy-search'),
			array($this, 'indexing_title_render'),
			'speedy_search_indexing_polyplugins',
			'speedy_search_indexing_section_polyplugins'
		);

		add_settings_field(
			'indexing_content',
			__('Index descriptions (post body)?', 'speedy-search'),
			array($this, 'indexing_content_render'),
			'speedy_search_indexing_polyplugins',
			'speedy_search_indexing_section_polyplugins'
		);

		add_settings_field(
			'indexing_categories',
			__('Index categories?', 'speedy-search'),
			array($this, 'indexing_categories_render'),
			'speedy_search_indexing_polyplugins',
			'speedy_search_indexing_section_polyplugins'
		);

		add_settings_field(
			'indexing_tags',
			__('Index tags?', 'speedy-search'),
			array($this, 'indexing_tags_render'),
			'speedy_search_indexing_polyplugins',
			'speedy_search_indexing_section_polyplugins'
		);

		add_settings_field(
			'indexing_author',
			__('Index post author?', 'speedy-search'),
			array($this, 'indexing_author_render'),
			'speedy_search_indexing_polyplugins',
			'speedy_search_indexing_section_polyplugins'
		);

    if (class_exists('WooCommerce')) {
      add_settings_field(
        'indexing_product_sku',
        __('Index product SKU?', 'speedy-search'),
        array($this, 'indexing_product_sku_render'),
        'speedy_search_indexing_polyplugins',
        'speedy_search_indexing_section_polyplugins'
      );

      add_settings_field(
        'indexing_product_custom_fields',
        __('Index custom product fields?', 'speedy-search'),
        array($this, 'indexing_product_custom_fields_render'),
        'speedy_search_indexing_polyplugins',
        'speedy_search_indexing_section_polyplugins'
      );
    }
  }

  /**
   * @return array
   */
  private function get_indexing_options() {
    $opts = Utils::get_option('indexing');

    return is_array($opts) ? $opts : array();
  }

  /**
   * @param string $key
   * @return bool
   */
  private function get_bool($key) {
    $opts = $this->get_indexing_options();

    if (!array_key_exists($key, $opts)) {
      return true;
    }

    return !empty($opts[$key]);
  }

  /**
   * @return void
   */
  public function indexing_title_render() {
    $option = $this->get_bool('title');
    ?>
    <div class="form-check form-switch">
      <input type="checkbox" name="speedy_search_settings_polyplugins[indexing][title]" class="form-check-input snappy-indexing-source" role="switch" <?php checked(true, $option, true); ?> /> <?php esc_html_e('Yes', 'speedy-search'); ?>
    </div>
    <p><strong><?php esc_html_e('Include the item title in indexed content.', 'speedy-search'); ?></strong></p>
		<?php
  }

  /**
   * @return void
   */
  public function indexing_content_render() {
    $option = $this->get_bool('content');
    ?>
    <div class="form-check form-switch">
      <input type="checkbox" name="speedy_search_settings_polyplugins[indexing][content]" class="form-check-input snappy-indexing-source" role="switch" <?php checked(true, $option, true); ?> /> <?php esc_html_e('Yes', 'speedy-search'); ?>
    </div>
    <p><strong><?php esc_html_e('Include the main post body (description) in indexed content.', 'speedy-search'); ?></strong></p>
		<?php
  }

  /**
   * @return void
   */
  public function indexing_categories_render() {
    $option = $this->get_bool('categories');
    ?>
    <div class="form-check form-switch">
      <input type="checkbox" name="speedy_search_settings_polyplugins[indexing][categories]" class="form-check-input snappy-indexing-source" role="switch" <?php checked(true, $option, true); ?> /> <?php esc_html_e('Yes', 'speedy-search'); ?>
    </div>
    <p><strong><?php esc_html_e('Include standard categories and product categories in indexed content.', 'speedy-search'); ?></strong></p>
		<?php
  }

  /**
   * @return void
   */
  public function indexing_tags_render() {
    $option = $this->get_bool('tags');
    ?>
    <div class="form-check form-switch">
      <input type="checkbox" name="speedy_search_settings_polyplugins[indexing][tags]" class="form-check-input snappy-indexing-source" role="switch" <?php checked(true, $option, true); ?> /> <?php esc_html_e('Yes', 'speedy-search'); ?>
    </div>
    <p><strong><?php esc_html_e('Include post tags and product tags in indexed content.', 'speedy-search'); ?></strong></p>
		<?php
  }

  /**
   * @return void
   */
  public function indexing_author_render() {
    $option = $this->get_bool('author');
    ?>
    <div class="form-check form-switch">
      <input type="checkbox" name="speedy_search_settings_polyplugins[indexing][author]" class="form-check-input snappy-indexing-source" role="switch" <?php checked(true, $option, true); ?> /> <?php esc_html_e('Yes', 'speedy-search'); ?>
    </div>
    <p><strong><?php esc_html_e('Index the post author name in the post index.', 'speedy-search'); ?></strong></p>
		<?php
  }

  /**
   * @return void
   */
  public function indexing_product_sku_render() {
    $option = $this->get_bool('product_sku');
    ?>
    <div class="form-check form-switch">
      <input type="checkbox" name="speedy_search_settings_polyplugins[indexing][product_sku]" class="form-check-input snappy-indexing-source" role="switch" <?php checked(true, $option, true); ?> /> <?php esc_html_e('Yes', 'speedy-search'); ?>
    </div>
    <p><strong><?php esc_html_e('Include WooCommerce SKU fields in the product index.', 'speedy-search'); ?></strong></p>
		<?php
  }

  /**
   * @return void
   */
  public function indexing_product_custom_fields_render() {
    $option = $this->get_bool('product_custom_fields');
    ?>
    <div class="form-check form-switch">
      <input type="checkbox" name="speedy_search_settings_polyplugins[indexing][product_custom_fields]" class="form-check-input snappy-indexing-source" role="switch" <?php checked(true, $option, true); ?> /> <?php esc_html_e('Yes', 'speedy-search'); ?>
    </div>
    <p><strong><?php esc_html_e('Include values from the Custom Product Fields list (Filters tab) in the product index.', 'speedy-search'); ?></strong></p>
		<?php
  }

}
