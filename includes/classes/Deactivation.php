<?php

namespace PolyPlugins\Speedy_Search;

if (!defined('ABSPATH')) exit;

class Deactivation {
  
  /**
   * init
   *
   * @return void
   */
  public static function init() {
    self::clear_cron();
  }
  
  /**
   * Clear cron
   *
   * @return void
   */
  private static function clear_cron() {
    wp_clear_scheduled_hook('speedy_search_background_worker');
    wp_clear_scheduled_hook('speedy_search_daily_background_worker');
    wp_clear_scheduled_hook('snappy_search_background_worker');
    wp_clear_scheduled_hook('snappy_search_daily_background_worker');
  }

}