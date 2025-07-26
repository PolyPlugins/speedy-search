<?php

namespace PolyPlugins\Speedy_Search\Backend;

use PolyPlugins\Speedy_Search\Utils;

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

  /**
   * Get top search terms from the last X days with at least 10 results
   *
   * @param string $type Optional. Filter by post type.
   * @param int    $limit Optional. Number of results to return.
   * @return array Array of top terms with their counts
   */
  public static function get_top_terms_last_x_days() {
    global $wpdb;

    $table           = $wpdb->prefix . self::TABLE_NAME;
		$popular_options = Utils::get_option('popular');
    $hits            = isset($popular_options['hits']) ? $popular_options['hits'] : 1;
    $limit           = isset($popular_options['limit']) ? $popular_options['limit'] : 5;
    $days            = isset($popular_options['days']) ? $popular_options['days'] : 30;
    $blacklist       = isset($popular_options['blacklist']) ? $popular_options['blacklist'] : array();
    $cutoff          = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));

    if (!empty($blacklist)) {
      $blacklist = array_map('trim', explode(',', $blacklist));
    }

    $placeholders = '';

    if (!empty($blacklist)) {
      $placeholders = implode(',', array_fill(0, count($blacklist), '%s'));
    }

    if (!empty($blacklist)) {
      $sql = $wpdb->prepare("
        SELECT term, COUNT(*) as count
        FROM %i
        WHERE searched_at >= %s
        AND result_count >= %d
        AND term NOT IN ($placeholders)
        GROUP BY term
        ORDER BY count DESC
        LIMIT %d
      ", array_merge(array($table, $cutoff, $hits), $blacklist, array($limit)));
    } else {
      $sql = $wpdb->prepare("
        SELECT term, COUNT(*) as count
        FROM %i
        WHERE searched_at >= %s
        AND result_count >= %d
        GROUP BY term
        ORDER BY count DESC
        LIMIT %d
      ", $table, $cutoff, $hits, $limit);
    }

    $results = $wpdb->get_results($sql, ARRAY_A);

    return $results;
  }

}