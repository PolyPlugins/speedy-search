<?php

namespace PolyPlugins\Speedy_Search;

use PolyPlugins\Speedy_Search\Utils;

class Log {

  private static $log_dir = null;

  private static $pruned_old_logs = false;

  /**
   * Log a debug message
   */
  public static function debug($message) {
    self::write_log('Debug', $message);
  }

  /**
   * Log an info message
   */
  public static function info($message) {
    self::write_log('Info', $message);
  }

  /**
   * Log a warning message
   */
  public static function warning($message) {
    self::write_log('Warning', $message);
  }

  /**
   * Log an error message
   */
  public static function error($message) {
    self::write_log('Error', $message);

    error_log($message);
  }

  /**
   * Check if debug logging is enabled
   */
  private static function is_debug_enabled() {
    $debug = Utils::get_option('debug');

    if (!is_array($debug)) {
      return false;
    }

    return !empty($debug['enabled']);
  }

  /**
   * Normalize message for logging
   */
  private static function normalize_message($message) {
    if (is_array($message) || is_object($message)) {
      return wp_json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    return (string) $message;
  }

  /**
   * Get log directory path
   *
   * @return string
   */
  private static function get_log_dir() {
    if (self::$log_dir === null) {
      $upload_dir = wp_upload_dir();
      self::$log_dir = $upload_dir['basedir'] . '/logs/snappy-search';
    }

    return self::$log_dir;
  }

  /**
   * Get log file path (one file per local calendar day, mm-dd-yy.log)
   *
   * @return string
   */
  private static function get_log_file() {
    return self::get_log_dir() . '/' . current_time('m-d-y') . '.log';
  }

  /**
   * Delete *.log files under the plugin log directory when older than 7 days (mtime).
   *
   * @return void
   */
  private static function prune_logs_older_than_seven_days() {
    if (self::$pruned_old_logs) {
      return;
    }

    self::$pruned_old_logs = true;

    $log_dir = self::get_log_dir();

    if (!is_dir($log_dir)) {
      return;
    }

    $cutoff = time() - (7 * DAY_IN_SECONDS);
    $files  = glob($log_dir . '/*.log');

    if (!is_array($files)) {
      return;
    }

    foreach ($files as $file) {
      if (!is_file($file)) {
        continue;
      }

      $mtime = filemtime($file);

      if ($mtime !== false && $mtime < $cutoff) {
        wp_delete_file($file);
      }
    }
  }

  /**
   * Ensure log directory exists with proper security
   *
   * @return bool
   */
  private static function ensure_log_directory() {
    $log_dir = self::get_log_dir();

    // Create directory if it doesn't exist
    if (!file_exists($log_dir)) {
      if (!wp_mkdir_p($log_dir)) {
        return false;
      }
    }

    // Create index.php file
    $index_file = $log_dir . '/index.php';
    if (!file_exists($index_file)) {
      file_put_contents($index_file, '<?php // Silence is golden');
    }

    // Create .htaccess file
    $htaccess_file = $log_dir . '/.htaccess';
    if (!file_exists($htaccess_file)) {
      file_put_contents($htaccess_file, 'deny from all');
    }

    // Parent uploads/logs may contain other data; deny web access there too.
    $logs_parent    = dirname($log_dir);
    $parent_htaccess = $logs_parent . '/.htaccess';
    if (is_dir($logs_parent) && !file_exists($parent_htaccess)) {
      file_put_contents($parent_htaccess, 'deny from all');
    }

    self::prune_logs_older_than_seven_days();

    return true;
  }

  /**
   * Write log entry to file
   *
   * @param string $level
   * @param mixed $message
   * @return void
   */
  private static function write_log($level, $message) {
    // If debug is not enabled, return
    if (!self::is_debug_enabled()) {
      return;
    }

    // Ensure directory exists
    if (!self::ensure_log_directory()) {
      return;
    }

    // Format timestamp in ISO 8601 format
    $timestamp = gmdate('Y-m-d\TH:i:sP');

    // Normalize message
    $normalized_message = self::normalize_message($message);

    // Format log line
    $log_line = sprintf("%s %s: %s\n", $timestamp, $level, $normalized_message);

    // Write to file
    $log_file = self::get_log_file();
    file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
  }

}
