<?php

namespace PolyPlugins\Speedy_Search;

class Updater {

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

  public function init() {
    add_action('wp', array($this, 'maybe_update'));
  }

  public function maybe_update() {
    $stored_version = get_option('speedy_search_version_polyplugins');

    if (!$stored_version) {
      $stored_version = $this->version;

      update_option('speedy_search_version_polyplugins', $this->version);
    }

    if (version_compare($stored_version, '1.2.0', '<')) {
      $stored_version = '1.2.0';

      $this->update_to_120();

      update_option('speedy_search_version_polyplugins', $stored_version);
    }

    if (version_compare($stored_version, '1.3.0', '<')) {
      $stored_version = '1.3.0';

      $this->update_to_130();

      update_option('speedy_search_version_polyplugins', $stored_version);
    }

    if (version_compare($stored_version, '1.4.0', '<')) {
      $stored_version = '1.4.0';

      $this->update_to_140();

      update_option('speedy_search_version_polyplugins', $stored_version);
    }
  }

  private function update_to_120() {
    global $wpdb;

    wp_clear_scheduled_hook('speedy_search_background_worker');
    
    if (!wp_next_scheduled('snappy_search_background_worker')) {
      wp_schedule_event(time(), 'every_minute', 'snappy_search_background_worker');
    }
    
    if (!wp_next_scheduled('snappy_search_daily_background_worker')) {
      wp_schedule_event(time(), 'daily', 'snappy_search_daily_background_worker');
    }

    $table_name      = $wpdb->prefix . 'ss_term_logs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "
      CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        term VARCHAR(255) NOT NULL,
        post_type VARCHAR(20) NOT NULL,
        result_count BIGINT UNSIGNED NOT NULL,
        searched_at DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id),
        KEY term (term),
        KEY post_type (post_type),
        KEY result_count (result_count),
        KEY searched_at (searched_at)
      ) $charset_collate;
    ";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    dbDelta($sql);
  }

  private function update_to_130() {
    update_option('speedy_search_notice_dismissed_polyplugins', false);
    
    Utils::update_option('database_type', 'sqlite');
  }

  private function update_to_140() {
    update_option('speedy_search_notice_dismissed_polyplugins', false);
  }

}