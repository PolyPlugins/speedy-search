<?php

namespace PolyPlugins\Speedy_Search\Backend;

use PolyPlugins\Speedy_Search\Utils;

class Notices {

  private $plugin;
  private $version;
  private $plugin_dir_url;
  private $notice_key = 'speedy_search_dismiss_notice';

  public function __construct($plugin, $version, $plugin_dir_url) {
    $this->plugin         = $plugin;
    $this->version        = $version;
    $this->plugin_dir_url = $plugin_dir_url;
  }

  public function init() {
    add_action('admin_notices', array($this, 'maybe_show_notice'));
    add_action('wp_ajax_speedy_search_dismiss_notice_nonce', array($this, 'dismiss_notice'));
  }

  public function maybe_show_notice() {
    $is_dismissed = get_option('speedy_search_notice_dismissed_polyplugins');

    if ($is_dismissed) {
      return;
    }
    
    $screen = get_current_screen();

    if ($screen->id != 'toplevel_page_speedy-search') {
      if ($this->version == '1.1.0') {
        $this->notice_110();
      }
      if ($this->version == '1.3.0') {
        $this->notice_130();
      }
      if ($this->version == '1.4.0') {
        $this->notice_140();
      }
      if ($this->version == '1.4.2') {
        $this->notice_142();
      }
    }
  }

  public function notice_110() {
    ?>
    <div class="notice notice-success is-dismissible speedy-search">
      <p><?php echo esc_html__('Speedy Search is now Snappy Search. We have change a lot including adding support for WooCommerce, EDD, and Pages. Indexes are also now updated when content is added, removed, or updated. We also added the ability to trigger reindexes and reverting the search to default when indexing. Try out the new features by visiting Snappy Search Settings.', 'speedy-search'); ?></p>
      <a href="options-general.php?page=speedy-search"><?php echo esc_html__('Try new features', 'speedy-search'); ?></a>
    </div>
    <?php
  }

  public function notice_130() {
    ?>
    <div class="notice notice-success is-dismissible speedy-search">
      <p><?php echo esc_html__('Snappy Search can now use MySQL databases, which means search indexes can be stored in your existing WordPress database for better performance.', 'speedy-search'); ?></p>
      <a href="options-general.php?page=speedy-search"><?php echo esc_html__('Switch to MySQL', 'speedy-search'); ?></a>
    </div>
    <?php
  }

  public function notice_140() {
    ?>
    <div class="notice notice-error is-dismissible speedy-search" style="padding-bottom: 10px;">
      <p><?php echo esc_html__('Starting with version 1.5.0, Snappy Search will include functionality offered by our ', 'speedy-search'); ?>
      <a href="https://wordpress.org/plugins/admin-instant-search/" target="_blank">Admin Instant Search</a>
      <?php echo esc_html__(' plugin. As part of this update, the plugin will gain the ability to index WooCommerce orders, which introduces additional security considerations.', 'speedy-search'); ?>
      <br /><br />
      <?php echo esc_html__('To support this securely and efficiently, we are ', 'speedy-search'); ?><strong><?php echo esc_html__('discontinuing support for SQLite', 'speedy-search'); ?></strong><?php echo esc_html__('. Managing mixed database logic (SQLite and MySQL) adds unnecessary complexity and overhead, particularly when handling sensitive data like order information.', 'speedy-search'); ?>
      <br /><br />
      <strong?><?php echo esc_html__('Before updating to 1.5.0, please ensure your site is using MySQL.', 'speedy-search'); ?></strong>
      <?php echo esc_html__('If your site is still using SQLite, search functionality will be automatically disabled after the update.', 'speedy-search'); ?></p>
      <a href="options-general.php?page=speedy-search"><?php echo esc_html__('Switch to MySQL', 'speedy-search'); ?></a>
    </div>
    <?php
  }

  public function notice_142() {
    ?>
    <div class="notice notice-error is-dismissible speedy-search" style="padding-bottom: 10px;">
      <p><?php echo esc_html__('Starting with version 1.5.0, Snappy Search will include functionality offered by our ', 'speedy-search'); ?>
      <a href="https://wordpress.org/plugins/admin-instant-search/" target="_blank">Admin Instant Search</a>
      <?php echo esc_html__(' plugin. As part of this update, the plugin will gain the ability to index WooCommerce orders, which introduces additional security considerations.', 'speedy-search'); ?>
      <br /><br />
      <?php echo esc_html__('To support this securely and efficiently, we are ', 'speedy-search'); ?><strong><?php echo esc_html__('discontinuing support for SQLite', 'speedy-search'); ?></strong><?php echo esc_html__('. Managing mixed database logic (SQLite and MySQL) adds unnecessary complexity and overhead, particularly when handling sensitive data like order information.', 'speedy-search'); ?>
      <br /><br />
      <strong?><?php echo esc_html__('Before updating to 1.5.0, please ensure your site is using MySQL.', 'speedy-search'); ?></strong>
      <?php echo esc_html__('If your site is still using SQLite, search functionality will be automatically disabled after the update.', 'speedy-search'); ?></p>
      <a href="options-general.php?page=speedy-search"><?php echo esc_html__('Switch to MySQL', 'speedy-search'); ?></a>
    </div>
    <?php
  }

  public function dismiss_notice() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'speedy_search_dismiss_notice_nonce')) {
      Utils::send_error('Invalid session', 403);
    }

    if (!current_user_can('manage_options')) {
      Utils::send_error('Unauthorized', 401);
    }

    update_option('speedy_search_notice_dismissed_polyplugins', true);
  }

}
