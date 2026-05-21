<?php

namespace PolyPlugins\Speedy_Search\Backend\Admin;

use PolyPlugins\Speedy_Search\Log;

if (!defined('ABSPATH')) exit;

class Product_Excluded_Terms {

  const META_KEY = '_snappy_search_excluded_terms';

  private $plugin;
  private $version;
  private $plugin_dir_url;

  public function __construct($plugin, $version, $plugin_dir_url) {
    $this->plugin         = $plugin;
    $this->version        = $version;
    $this->plugin_dir_url = $plugin_dir_url;
  }

  public function init() {
    Log::debug('Snappy Search product excluded terms field initialized.');

    add_filter('woocommerce_product_data_tabs', array($this, 'add_search_tab'));
    add_action('woocommerce_product_data_panels', array($this, 'render_panel'));
    add_action('admin_head', array($this, 'output_tab_icon_css'));
    add_action('woocommerce_process_product_meta', array($this, 'save_field'));
  }

  /**
   * @param array $tabs
   * @return array
   */
  public function add_search_tab($tabs) {
    $tabs['snappy_search'] = array(
      'label'    => __('Search', 'speedy-search'),
      'target'   => 'snappy_search_product_data',
      'class'    => array('snappy-search-product-tab'),
      'priority' => 90,
    );

    return $tabs;
  }

  /**
   * @return void
   */
  public function render_panel() {
    global $post;

    if (!$post || !isset($post->ID)) {
      return;
    }

    $meta_key = self::META_KEY;
    $value    = get_post_meta($post->ID, $meta_key, true);
    $value    = sanitize_text_field((string) $value);

    ?>
    <div id="snappy_search_product_data" class="panel woocommerce_options_panel hidden">
      <div class="options_group">
        <?php if (function_exists('woocommerce_wp_textarea_input')) : ?>
          <?php woocommerce_wp_textarea_input(array(
            'id'          => $meta_key,
            'label'       => __('Excluded Search Terms', 'speedy-search'),
            'description' => __('Comma-separated terms that should hide this product from matching searches.', 'speedy-search'),
            'desc_tip'    => true,
            'value'       => $value,
          )); ?>
        <?php else : ?>
          <p class="form-field form-field-wide">
            <label for="<?php echo esc_attr($meta_key); ?>"><?php esc_html_e('Excluded Search Terms', 'speedy-search'); ?></label>
            <textarea id="<?php echo esc_attr($meta_key); ?>" name="<?php echo esc_attr($meta_key); ?>" rows="3"><?php echo esc_textarea($value); ?></textarea>
            <span class="description"><?php esc_html_e('Comma-separated terms that should hide this product from matching searches.', 'speedy-search'); ?></span>
          </p>
        <?php endif; ?>
      </div>
    </div>
    <?php
  }

  /**
   * @return void
   */
  public function output_tab_icon_css() {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;

    if (!$screen || $screen->id !== 'product') {
      return;
    }
    ?>
    <style>
      #woocommerce-product-data ul.wc-tabs li.snappy-search-product-tab a:before {
        font-family: Dashicons !important;
        content: "\f179" !important;
      }
    </style>
    <?php
  }

  public function save_field($product_id) {
    $product_id = absint($product_id);

    if ($product_id < 1) {
      return;
    }

    if (!current_user_can('edit_post', $product_id)) {
      return;
    }

    if (!isset($_POST['woocommerce_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['woocommerce_meta_nonce'])), 'woocommerce_save_data')) {
      return;
    }

    $meta_key = self::META_KEY;
    $raw      = '';

    if (isset($_POST[$meta_key])) {
      $raw = sanitize_textarea_field(wp_unslash($_POST[$meta_key]));
    }

    $clean    = $this->normalize_comma_separated_terms($raw);

    if ($clean === '') {
      delete_post_meta($product_id, $meta_key);
      return;
    }

    update_post_meta($product_id, $meta_key, $clean);
  }

  /**
   * @param mixed $value
   * @return string
   */
  private function normalize_comma_separated_terms($value) {
    $parts = array_filter(array_map('trim', explode(',', (string) $value)));
    $parts = array_unique(array_map('sanitize_text_field', $parts));
    $terms = array();

    foreach ($parts as $part) {
      $part = strtolower(trim((string) $part));
      $part = preg_replace('/\s+/', ' ', $part);

      if ($part !== '') {
        $terms[] = $part;
      }
    }

    if ($terms === array()) {
      return '';
    }

    return implode(', ', array_values(array_unique($terms)));
  }

}
