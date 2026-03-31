<?php

namespace PolyPlugins\Speedy_Search;

use PolyPlugins\Speedy_Search\TNTSearch;

if (!defined('ABSPATH')) exit;

class Utils {

  /**
   * Get speedy search options
   *
   * @return array $options The speedy search options
   */
  public static function get_options() {
    $options = get_option('speedy_search_settings_polyplugins');

    return $options;
  }
  
  /**
   * Get speedy search option from options array
   *
   * @param  string $option The option to retrieve from options
   * @return mixed  $option The retrieved option value
   */
  public static function get_option($option) {
    $options = self::get_options();
    $option  = isset($options[$option]) ? $options[$option] : false;

    return $option;
  }
  
  /**
   * Update an option
   *
   * @param  string $option The option name
   * @param  mixed  $value  The option value
   * @return void
   */
  public static function update_option($option, $value) {
    $options          = self::get_options();
    $options[$option] = $value;

    update_option('speedy_search_settings_polyplugins', $options);
  }

  /**
   * Get API cache value
   *
   * @param  string $key Cache key
   * @return mixed
   */
  public static function get_api_cache($key) {
    return wp_cache_get($key, 'speedy_search_api');
  }

  /**
   * Set API cache value and register the key
   *
   * @param  string $key Cache key
   * @param  mixed  $value Cache value
   * @param  int    $expiration Cache expiration in seconds
   * @return void
   */
  public static function set_api_cache($key, $value, $expiration = 600) {
    wp_cache_set($key, $value, 'speedy_search_api', $expiration);

    $keys = get_option('speedy_search_api_cache_keys', array());

    if (!is_array($keys)) {
      $keys = array();
    }

    $keys[$key] = time();

    update_option('speedy_search_api_cache_keys', $keys);
  }

  /**
   * Clear API cache group
   *
   * @return void
   */
  public static function clear_api_cache() {
    $did_flush_group = false;

    if (function_exists('wp_cache_flush_group')) {
      if (function_exists('wp_cache_supports')) {
        if (wp_cache_supports('flush_group')) {
          wp_cache_flush_group('speedy_search_api');
          $did_flush_group = true;
        }
      } else {
        wp_cache_flush_group('speedy_search_api');
        $did_flush_group = true;
      }
    }

    if (!$did_flush_group) {
      $keys = get_option('speedy_search_api_cache_keys', array());

      if (is_array($keys) && !empty($keys)) {
        foreach ($keys as $key => $timestamp) {
          wp_cache_delete($key, 'speedy_search_api');
        }
      }
    }

    delete_option('speedy_search_api_cache_keys');
  }

  /**
   * Get speedy search indexes
   *
   * @return array $options The speedy search indexes options
   */
  public static function get_indexes() {
    $options = get_option('speedy_search_indexes_polyplugins');

    return $options ? $options : array();
  }
  
  /**
   * Get speedy search index from options array
   *
   * @param  string $option The option to retrieve from options
   * @return mixed  $option The retrieved option value
   */
  public static function get_index($option) {
    $options = self::get_indexes();
    $option  = isset($options[$option]) ? $options[$option] : false;

    return $option;
  }
  
  /**
   * Update an index
   *
   * @param  string $index  The index name
   * @param  string $option The option name
   * @param  mixed  $value  The option value
   * @return void
   */
  public static function update_index($index, $option, $value) {
    $options                  = self::get_indexes();
    $options[$index][$option] = $value;

    update_option('speedy_search_indexes_polyplugins', $options);
  }
  
  /**
   * Check if currently indexing
   *
   * @return bool $is_indexing The indexing status
   */
  public static function is_indexing() {
    $allowed_post_types = self::get_allowed_post_types();
    $is_indexing        = false;

    foreach($allowed_post_types as $allowed_post_type => $label) {
      $type        = $allowed_post_type . 's';
      $index       = self::get_index($type);
      $is_indexing = isset($index['complete']) ? false : true;

      if ($is_indexing) {
        return $is_indexing;
      }
    }

    return $is_indexing;
  }

  /**
   * Delete an index file
   *
   * @param  string $index_path The path to the index file
   * @param  string $filename   The index filename (e.g., 'posts.sqlite')
   * @return bool               True if deleted, false otherwise
   */
  public static function delete_index($index_path, $filename) {
    $path = rtrim($index_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    if (file_exists($path)) {
      return wp_delete_file($path);
    }

    return false;
  }
  
  /**
   * Flush DB index
   *
   * @param  string $post_type The post type
   * @return void
   */
  public static function delete_db_index($post_type) {
    $tnt        = TNTSearch::get_instance()->tnt();
    $index_name = self::get_index_name($post_type);

    try {
      $tnt->selectIndex($index_name);
    } catch (\Exception $e) {
      return;
    }

    $tnt->engine->flushIndex($index_name);
  }
  
  /**
   * Reindex
   *
   * @return void
   */
  public static function reindex() {
    $index_path         = TNTSearch::get_instance()->get_index_path();
    $database_type      = self::get_option('database_type') ?: 'mysql';
    $allowed_post_types = self::get_allowed_post_types();

    if ($database_type === 'mysql') {
      // Reset indexing progress
      foreach ($allowed_post_types as $allowed_post_type => $label) {
        self::delete_db_index($allowed_post_type);
      }

      delete_option('speedy_search_indexes_polyplugins');
    } else {
      // Reset indexing progress
      foreach ($allowed_post_types as $allowed_post_type => $label) {
        $index_name = self::get_index_name($allowed_post_type);

        self::delete_index($index_path, $index_name);
      }

      delete_option('speedy_search_indexes_polyplugins');
    }
  }
  
  /**
   * Send success json
   *
   * @param  string $message The message to send
   * @param  int    $code    The status code
   * @return void
   */
  public static function send_success($message, $code = 200) {
    $message = $message ? sanitize_text_field($message) : __('Success', 'speedy-search');
    $code    = is_numeric($code) ? (int) $code : 200;

    wp_send_json_success(array(
      'message' => sanitize_text_field($message),
      'status' => $code
    ), $code);
  }
  
  /**
   * Send error json
   *
   * @param  mixed $message
   * @param  mixed $code
   * @return void
   */
  public static function send_error($message, $code = 400) {
    $message = $message ? sanitize_text_field($message) : __('Error', 'speedy-search');
    $code    = is_numeric($code) ? (int) $code : 400;

    wp_send_json_error(array(
      'message' => sanitize_text_field($message),
      'status' => $code
    ), $code);
  }
  
  /**
   * Get allowed post types
   *
   * @return array $allowed_post_types The allowed post types
   */
  public static function get_allowed_post_types() {
    $allowed_post_types = array(
      'post' => __('Post', 'speedy-search'),
      'page' => __('Page', 'speedy-search')
    );

    if (class_exists('WooCommerce')) {
      $allowed_post_types['product'] = __('Product', 'speedy-search');
    }

    if (class_exists('Easy_Digital_Downloads')) {
      $allowed_post_types['download'] = __('Download', 'speedy-search');
    }

    return $allowed_post_types;
  }
  
  /**
   * Get allowed types
   *
   * @return array $allowed_types The allowed types
   */
  public static function get_allowed_types() {
    $allowed_post_types = self::get_allowed_post_types();

    $allowed_types = array();

    foreach ($allowed_post_types as $key => $label) {
      $key                 = $key . 's';
      $allowed_types[$key] = $label . __('s', 'speedy-search');
    }

    return $allowed_types;
  }
  
  /**
   * Get index name
   *
   * @param  string $post_type  The type of index
   * @return string $index      The index name
   */
  public static function get_index_name($post_type) {
    $type          = $post_type . 's';
		$database_type = self::get_option('database_type') ?: 'mysql';
    $index_name    = '';

    if ($database_type === 'mysql') {
      $index_name = 'wp_ss_' . $type;
    } else {
      $index_name = $type . '.sqlite';
    }

    return $index_name;
  }

  /**
   * Get the slug of the page using the advanced search template
   *
   * @return string|false
   */
  public static function get_page_slug_by_template() {
    $args = array(
      'post_type'      => 'page',
      'posts_per_page' => 1,
      'post_status'    => 'publish',
      'meta_key'       => '_wp_page_template',
      'meta_value'     => 'snappy-search-advanced-search-form.php',
      'fields'         => 'ids',
    );

    $default = get_posts($args);

    if (!empty($default)) {
      $slug = get_post_field('post_name', $default[0]);

      return $slug;
    } else {
      $args = array(
        'post_type'      => 'page',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
        'meta_key'       => '_wp_page_template',
        'meta_value'     => 'snappy-search-advanced-search-form-stacked.php',
        'fields'         => 'ids',
      );

      $stacked = get_posts($args);

      if (!empty($stacked)) {
        $slug = get_post_field('post_name', $stacked[0]);

        return $slug;
      }
    }

    return false;
  }
  
  /**
   * Checks for any missing extensions
   *
   * @return mixed $is_missing_extension Array of missing extensions or false
   */
  public static function is_missing_extensions() {
		$database_type      = self::get_option('database_type') ?: 'mysql';
    $missing_extensions = array();

    $extensions = array(
      'PDO',
      'mbstring'
    );
    
    if ($database_type === 'sqlite') {
      $extensions[] = 'pdo_sqlite';
    }

    foreach ($extensions as $extension) {
      if (!extension_loaded($extension)) {
        $missing_extensions[] = $extension;
      }
    }

    $is_missing_extensions = $missing_extensions ? $missing_extensions : false;

    return $is_missing_extensions;
  }

  /**
   * Convert Hex color to RGBA
   *
   * @param  mixed $hex
   * @param  mixed $alpha
   * @return void
   */
  public static function hex_to_rgba($hex, $alpha = null) {
    // Remove the '#' if present
    $hex = ltrim($hex, '#');
    
    // Get the red, green, and blue values
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    // If alpha is provided, return rgba format, otherwise return rgb
    if ($alpha !== null) {
      if ($alpha > 1) {
        $alpha = $alpha / 100; // Convert percentage to decimal
      }

      return "rgba($r, $g, $b, $alpha)";
    } else {
      return "rgb($r, $g, $b)";
    }
  }

  /**
   * Strip shortcodes and oversized tokens from plain text.
   *
   * @param string $value            Text to sanitize.
   * @param int    $max_token_length Maximum token length allowed.
   * @return string
   */
  public static function sanitize_index_text($value, $max_token_length = 255) {
    if (!is_string($value) || $value === '') {
      return $value;
    }

    if (function_exists('strip_shortcodes')) {
      $value = strip_shortcodes($value);
    }

    // Remove shortcode-like wrappers even if shortcode tags are not registered.
    $value = preg_replace('/\[(?:\/)?[a-zA-Z0-9_-]+(?:\s[^\]]*)?\]/', ' ', $value);

    $max_token_length = is_numeric($max_token_length) ? (int) $max_token_length : 255;

    if ($max_token_length < 1) {
      return $value;
    }

    $min_oversized_length = $max_token_length + 1;
    $pattern              = '/\S{' . $min_oversized_length . ',}/u';
    $sanitized            = preg_replace($pattern, ' ', (string) $value);

    // Fallback if unicode mode fails for edge-case invalid UTF-8.
    if ($sanitized === null) {
      $pattern   = '/\S{' . $min_oversized_length . ',}/';
      $sanitized = preg_replace($pattern, ' ', (string) $value);
    }

    return is_string($sanitized) ? $sanitized : $value;
  }

  /**
   * Strip oversized tokens from each string field in an index document.
   *
   * @param array $document          Key/value data passed to TNTSearch insert.
   * @param int   $max_token_length  Maximum token length allowed.
   * @return array
   */
  public static function sanitize_index_document($document, $max_token_length = 255) {
    if (!is_array($document)) {
      return $document;
    }

    foreach ($document as $key => $value) {
      if (!is_string($value)) {
        continue;
      }

      $document[$key] = self::sanitize_index_text($value, $max_token_length);
    }

    return $document;
  }

  public static function prevent_predis_autoload_conflict($autoloader) {
    if (!is_object($autoloader) || !method_exists($autoloader, 'loadClass')) {
      return;
    }

    spl_autoload_unregister(array($autoloader, 'loadClass'));
    spl_autoload_register(function ($class) use ($autoloader) {
      if (strpos($class, 'Predis\\') === 0) {
        return false;
      }

      return $autoloader->loadClass($class);
    }, true, true);
  }

}