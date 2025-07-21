<?php

namespace PolyPlugins\Speedy_Search\Backend;

class DB {

  const TABLE_NAME = 'ss_term_logs';
  
  /**
   * Get term counts
   *
   * @param  string $term The search term
   * @param  string $type The post type
   * @return object $term The term object
   */
  public static function get_term_counts($term, $type) {
    $cache_key = 'speedy_search_term_counts_' . md5($term . '_' . $type);
    $term_count = wp_cache_get($cache_key, 'speedy_search');

    if (!$term_count) {
      global $wpdb;

      $table = $wpdb->prefix . self::TABLE_NAME;

      $sql = $wpdb->prepare("
        SELECT COUNT(*) 
        FROM $table
        WHERE term = %s 
        AND post_type = %s
      ", $term, $type);

      $term_count = (int) $wpdb->get_var($sql);

      wp_cache_set($cache_key, $term_count, 'speedy_search_term_counts', 600);
    }

    return $term_count;
  }
  
  /**
   * Insert term
   *
   * @param  string $term The search term
   * @param  string $type The post type
   * @return void
   */
  public static function insert_term_log($term, $type, $result_count) {
    global $wpdb;

    $table = $wpdb->prefix . self::TABLE_NAME;
    $now   = current_time('mysql');
    
    $wpdb->insert(
      $table,
      array(
        'term'         => strtolower($term),
        'post_type'    => $type,
        'result_count' => $result_count,
        'searched_at'  => $now,
      ),
      array('%s', '%s', '%d', '%s')
    );
  }
  
  /**
   * Update term cont
   *
   * @param  int   $id    The term id
   * @param  int   $count The number of times the search terms has been searched
   * @return void
   */
  public static function update_term_count($id, $count) {
    global $wpdb;

    $table = $wpdb->prefix . self::TABLE_NAME;
    $now   = time();
    
    $wpdb->update(
      $table,
      array(
        'count'         => $count,
        'searched_at' => $now,
      ),
      array(
        'id' => $id,
      ),
      array('%d', '%s'),
      array('%d')
    );
  }

}