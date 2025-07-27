<?php

namespace PolyPlugins\Speedy_Search;

if (!defined('ABSPATH')) exit;

class Activation {
  
  /**
   * init
   *
   * @return void
   */
  public static function init() {
    self::schedule_cron();
    self::set_default_options();
    self::create_tables();
  }
  
  /**
   * Schedule cron
   *
   * @return void
   */
  private static function schedule_cron() {
    if (!wp_next_scheduled('snappy_search_background_worker')) {
      wp_schedule_event(time(), 'every_minute', 'snappy_search_background_worker');
    }

    if (!wp_next_scheduled('snappy_search_daily_background_worker')) {
      wp_schedule_event(time(), 'daily', 'snappy_search_daily_background_worker');
    }
  }
  
  /**
   * Set default options
   *
   * @return void
   */
  private static function set_default_options() {
    $default_options = array(
      'enabled' => false,
    );

    add_option('speedy_search_settings_polyplugins', $default_options);
  }
  
  /**
   * Create tables
   *
   * @return void
   */
  private static function create_tables() {
    global $wpdb;

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

}