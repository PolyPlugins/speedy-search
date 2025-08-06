<?php

namespace PolyPlugins\Speedy_Search\Backend;

use PolyPlugins\Speedy_Search\Utils;

class DB {

  const TABLE_NAME = 'ss_term_logs';
  
  /**
   * Insert term
   *
   * @param  string $term The search term
   * @param  string $type The post type
   * @return void
   */
  public static function insert_term_log($term, $type, $result_count) {
    global $wpdb;

    $allowed_types = Utils::get_allowed_post_types();

    if (!array_key_exists($type, $allowed_types)) {
      return;
    }

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
    $result_count    = isset($popular_options['result_count']) ? $popular_options['result_count'] : 10;
    $limit           = isset($popular_options['limit']) ? $popular_options['limit'] : 5;
    $days            = isset($popular_options['days']) ? $popular_options['days'] : 30;
    $blacklist_raw   = isset($popular_options['blacklist']) ? $popular_options['blacklist'] : '';
    $blacklist       = array_filter(array_map('trim', explode(',', $blacklist_raw)));
    $cutoff          = gmdate('Y-m-d H:i:s', strtotime('-' . $days . ' days'));

    if (!empty($blacklist)) {
      $args = array_merge(array($table, $cutoff, $result_count), $blacklist, array($limit));

      $results = $wpdb->get_results($wpdb->prepare("
        SELECT term, COUNT(*) as count
        FROM %i
        WHERE searched_at >= %s
        AND result_count >= %d
        AND term NOT IN (" . implode(',', array_fill(0, count($blacklist), '%s')) . ")
        GROUP BY term
        ORDER BY count DESC
        LIMIT %d
      ", $args), ARRAY_A);
    } else {
      $results = $wpdb->get_results($wpdb->prepare("
        SELECT term, COUNT(*) as count
        FROM %i
        WHERE searched_at >= %s
        AND result_count >= %d
        GROUP BY term
        ORDER BY count DESC
        LIMIT %d
      ", $table, $cutoff, $result_count, $limit), ARRAY_A);
    }

    return $results;
  }

  /**
   * Get top search terms from the last X days with at least 10 results
   *
   * @param string $type Optional. Filter by post type.
   * @param int    $limit Optional. Number of results to return.
   * @return array Array of top terms with their counts
   */
  public static function delete_terms_older_than_x_days($days = 30) {
    global $wpdb;

    $table       = $wpdb->prefix . self::TABLE_NAME;
    $cutoff_date = gmdate('Y-m-d H:i:s', strtotime('-' . $days . ' days'));

    $wpdb->query(
      $wpdb->prepare(
        "DELETE FROM %i WHERE searched_at < %s",
        $table,
        $cutoff_date
      )
    );
  }

}