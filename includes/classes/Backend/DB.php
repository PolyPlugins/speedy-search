<?php

namespace PolyPlugins\Speedy_Search\Backend;

use PolyPlugins\Speedy_Search\Log;
use PolyPlugins\Speedy_Search\Utils;

if (!defined('ABSPATH')) exit;

class DB {

  const TABLE_NAME = 'ss_term_logs';

  const STATUS_TABLE = 'ss_term_status';

  /**
   * Skip repeated status-table checks in the same request.
   *
   * @var bool
   */
  private static $status_ready = false;
  
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

    $term = self::normalize_term($term);

    if ($term === '') {
      return;
    }

    $table = $wpdb->prefix . self::TABLE_NAME;
    $now   = current_time('mysql');
    
    $wpdb->insert(
      $table,
      array(
        'term'         => $term,
        'post_type'    => $type,
        'result_count' => $result_count,
        'searched_at'  => $now,
      ),
      array('%s', '%s', '%d', '%s')
    );

    if (!empty($wpdb->last_error)) {
      Log::warning(sprintf('Term log insert failed: %s', $wpdb->last_error));
    }
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

    if (!empty($wpdb->last_error)) {
      Log::warning(sprintf('Term log update failed: %s', $wpdb->last_error));
    }
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

    self::ensure_status_table();

    $log_table       = $wpdb->prefix . self::TABLE_NAME;
    $status_table    = $wpdb->prefix . self::STATUS_TABLE;
		$popular_options = Utils::get_option('popular');
    $result_count    = isset($popular_options['result_count']) ? $popular_options['result_count'] : 10;
    $limit           = isset($popular_options['limit']) ? (int) $popular_options['limit'] : 5;
    $days            = isset($popular_options['days']) ? $popular_options['days'] : 30;
    $cutoff          = gmdate('Y-m-d H:i:s', strtotime('-' . $days . ' days'));
    $term_source     = isset($popular_options['term_source']) ? $popular_options['term_source'] : 'manual';
    $results         = array();
    $seen            = array();

    if (!in_array($term_source, array('manual', 'automatic', 'both'), true)) {
      $term_source = 'manual';
    }

    if ($limit < 1) {
      return array();
    }

    if ($term_source !== 'automatic') {
      $manual_terms = $wpdb->get_results($wpdb->prepare(
        "SELECT term FROM %i WHERE status = 'approved' ORDER BY position ASC, term ASC LIMIT %d",
        $status_table,
        $limit
      ), ARRAY_A);

      if (!is_array($manual_terms)) {
        $manual_terms = array();
      }

      foreach ($manual_terms as $row) {
        if (empty($row['term'])) {
          continue;
        }

        $results[] = array(
          'term'  => $row['term'],
          'count' => 0,
        );
        $seen[$row['term']] = true;

        if (count($results) >= $limit) {
          return $results;
        }
      }
    }

    if ($term_source === 'manual') {
      return $results;
    }

    $tracked = $wpdb->get_results($wpdb->prepare(
      "SELECT logs.term, COUNT(*) as count
      FROM %i AS logs
      INNER JOIN %i AS status_rows ON status_rows.term = logs.term AND status_rows.status = 'approved' AND status_rows.source <> 'manual'
      WHERE logs.searched_at >= %s
      AND logs.result_count >= %d
      GROUP BY logs.term ORDER BY count DESC LIMIT %d",
      $log_table,
      $status_table,
      $cutoff,
      $result_count,
      $limit
    ), ARRAY_A);

    if (!is_array($tracked)) {
      return $results;
    }

    foreach ($tracked as $row) {
      if (empty($row['term']) || isset($seen[$row['term']])) {
        continue;
      }

      $results[] = $row;

      if (count($results) >= $limit) {
        break;
      }
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

    if (!empty($wpdb->last_error)) {
      Log::warning(sprintf('Term log cleanup failed: %s', $wpdb->last_error));
    }
  }

  /**
   * Create the term status table when it is missing and approve terms already logged.
   *
   * @return void
   */
  public static function ensure_status_table() {
    if (self::$status_ready) {
      return;
    }

    global $wpdb;

    $table = $wpdb->prefix . self::STATUS_TABLE;
    $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));

    if ($found !== $table) {
      self::create_status_table();
      $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));

      if ($found !== $table) {
        return;
      }

      delete_option('speedy_search_term_status_backfilled');
    }

    if (!get_option('speedy_search_term_status_backfilled')) {
      $backfilled = self::backfill_term_status();

      if ($backfilled === false) {
        return;
      }

      update_option('speedy_search_term_status_backfilled', 1, false);
    }

    self::ensure_position_column();
    self::$status_ready = true;
    self::assign_missing_positions();
    self::migrate_blacklist_setting();
  }

  /**
   * Save a status for a tracked search when the term has none yet.
   *
   * Blacklisted terms stay blacklisted. A search stays pending until it is approved.
   *
   * @param string $term Search term.
   * @return void
   */
  public static function record_tracked_term($term) {
    self::ensure_status_table();

    $term = self::normalize_term($term);

    if ($term === '' || self::get_term_status($term)) {
      return;
    }

    self::set_term_status($term, 'pending', 'tracked');
  }

  /**
   * Read one term status row.
   *
   * @param string $term Search term.
   * @return array|null
   */
  public static function get_term_status($term) {
    global $wpdb;

    self::ensure_status_table();

    $term = self::normalize_term($term);

    if ($term === '') {
      return null;
    }

    $row = $wpdb->get_row($wpdb->prepare(
      'SELECT term, status, source, position FROM %i WHERE term = %s',
      $wpdb->prefix . self::STATUS_TABLE,
      $term
    ), ARRAY_A);

    return is_array($row) ? $row : null;
  }

  /**
   * Insert or replace a term status.
   *
   * @param string $term   Search term.
   * @param string $status pending, approved, or rejected.
   * @param string $source tracked, manual, or auto.
   * @return bool False when the added list is already at twice the popular limit.
   */
  public static function set_term_status($term, $status, $source) {
    global $wpdb;

    self::ensure_status_table();

    $term     = self::normalize_term($term);
    $statuses = array('pending', 'approved', 'rejected');
    $sources  = array('tracked', 'manual', 'auto');

    if ($term === '' || !in_array($status, $statuses, true) || !in_array($source, $sources, true)) {
      return false;
    }

    $existing = self::get_term_status($term);
    $already  = is_array($existing) && isset($existing['status']) && $existing['status'] === 'approved';
    $position = (is_array($existing) && isset($existing['position'])) ? (int) $existing['position'] : 0;

    if ($status === 'approved' && !$already && self::count_terms_by_status('approved') >= self::added_term_cap()) {
      return false;
    }

    if ($status === 'approved' && !$already) {
      $position = self::next_term_position();
    }

    $wpdb->replace(
      $wpdb->prefix . self::STATUS_TABLE,
      array(
        'term'     => $term,
        'status'   => $status,
        'source'   => $source,
        'position' => $position,
      ),
      array('%s', '%s', '%s', '%d')
    );

    if (!empty($wpdb->last_error)) {
      Log::warning(sprintf('Term status save failed: %s', $wpdb->last_error));
      return false;
    }

    return true;
  }

  /**
   * Popular limit used for the public term list.
   *
   * @return int
   */
  public static function popular_limit() {
    $popular = Utils::get_option('popular');
    $limit   = (is_array($popular) && isset($popular['limit']) && is_numeric($popular['limit'])) ? (int) $popular['limit'] : 5;

    return $limit > 0 ? $limit : 1;
  }

  /**
   * How many added or pending terms the Terms screen keeps.
   *
   * @return int
   */
  public static function added_term_cap() {
    return self::popular_limit() * 2;
  }

  /**
   * Count terms with one status.
   *
   * @param string $status pending, approved, or rejected.
   * @return int
   */
  public static function count_terms_by_status($status) {
    global $wpdb;

    self::ensure_status_table();

    $statuses = array('pending', 'approved', 'rejected');

    if (!in_array($status, $statuses, true)) {
      return 0;
    }

    return (int) $wpdb->get_var($wpdb->prepare(
      'SELECT COUNT(*) FROM %i WHERE status = %s',
      $wpdb->prefix . self::STATUS_TABLE,
      $status
    ));
  }

  /**
   * Save the drag order for added terms.
   *
   * @param array $terms Terms in display order.
   * @return void
   */
  public static function set_term_positions($terms) {
    global $wpdb;

    self::ensure_status_table();

    if (!is_array($terms)) {
      return;
    }

    $position = 1;

    foreach ($terms as $term) {
      $term = self::normalize_term($term);

      if ($term === '') {
        continue;
      }

      $wpdb->update(
        $wpdb->prefix . self::STATUS_TABLE,
        array(
          'position' => $position,
        ),
        array(
          'term'   => $term,
          'status' => 'approved',
        ),
        array('%d'),
        array('%s', '%s')
      );

      $position++;
    }
  }

  /**
   * Next position at the end of the added list.
   *
   * @return int
   */
  private static function next_term_position() {
    global $wpdb;

    $max = $wpdb->get_var($wpdb->prepare(
      "SELECT MAX(position) FROM %i WHERE status = 'approved'",
      $wpdb->prefix . self::STATUS_TABLE
    ));

    return (int) $max + 1;
  }

  /**
   * List terms with one status.
   *
   * @param string $status pending, approved, or rejected.
   * @param string $source Optional. manual, or not_manual to skip terms you added.
   * @param int    $limit  Optional. Maximum rows. 0 returns all matches.
   * @return array
   */
  public static function get_terms_by_status($status, $source = '', $limit = 0) {
    global $wpdb;

    self::ensure_status_table();

    $statuses = array('pending', 'approved', 'rejected');
    $sources  = array('', 'manual', 'not_manual');

    if (!in_array($status, $statuses, true) || !in_array($source, $sources, true)) {
      return array();
    }

    $status_table = $wpdb->prefix . self::STATUS_TABLE;
    $log_table    = $wpdb->prefix . self::TABLE_NAME;
    $limit        = (int) $limit;
    $rows         = null;

    if ($status === 'pending' && $source === 'manual' && $limit > 0) {
      $rows = $wpdb->get_results($wpdb->prepare(
        'SELECT status_rows.term, status_rows.status, status_rows.source, status_rows.position, COUNT(logs.id) AS searches FROM %i AS status_rows LEFT JOIN %i AS logs ON logs.term = status_rows.term WHERE status_rows.status = %s AND status_rows.source = %s GROUP BY status_rows.term, status_rows.status, status_rows.source, status_rows.position ORDER BY status_rows.term ASC LIMIT %d',
        $status_table,
        $log_table,
        $status,
        'manual',
        $limit
      ), ARRAY_A);
    } elseif ($status === 'pending' && $source === 'manual') {
      $rows = $wpdb->get_results($wpdb->prepare(
        'SELECT status_rows.term, status_rows.status, status_rows.source, status_rows.position, COUNT(logs.id) AS searches FROM %i AS status_rows LEFT JOIN %i AS logs ON logs.term = status_rows.term WHERE status_rows.status = %s AND status_rows.source = %s GROUP BY status_rows.term, status_rows.status, status_rows.source, status_rows.position ORDER BY status_rows.term ASC',
        $status_table,
        $log_table,
        $status,
        'manual'
      ), ARRAY_A);
    } elseif ($status === 'pending' && $source === 'not_manual' && $limit > 0) {
      $rows = $wpdb->get_results($wpdb->prepare(
        'SELECT status_rows.term, status_rows.status, status_rows.source, status_rows.position, COUNT(logs.id) AS searches FROM %i AS status_rows LEFT JOIN %i AS logs ON logs.term = status_rows.term WHERE status_rows.status = %s AND status_rows.source <> %s GROUP BY status_rows.term, status_rows.status, status_rows.source, status_rows.position ORDER BY status_rows.term ASC LIMIT %d',
        $status_table,
        $log_table,
        $status,
        'manual',
        $limit
      ), ARRAY_A);
    } elseif ($status === 'pending' && $source === 'not_manual') {
      $rows = $wpdb->get_results($wpdb->prepare(
        'SELECT status_rows.term, status_rows.status, status_rows.source, status_rows.position, COUNT(logs.id) AS searches FROM %i AS status_rows LEFT JOIN %i AS logs ON logs.term = status_rows.term WHERE status_rows.status = %s AND status_rows.source <> %s GROUP BY status_rows.term, status_rows.status, status_rows.source, status_rows.position ORDER BY status_rows.term ASC',
        $status_table,
        $log_table,
        $status,
        'manual'
      ), ARRAY_A);
    } elseif ($status === 'pending' && $limit > 0) {
      $rows = $wpdb->get_results($wpdb->prepare(
        'SELECT status_rows.term, status_rows.status, status_rows.source, status_rows.position, COUNT(logs.id) AS searches FROM %i AS status_rows LEFT JOIN %i AS logs ON logs.term = status_rows.term WHERE status_rows.status = %s GROUP BY status_rows.term, status_rows.status, status_rows.source, status_rows.position ORDER BY status_rows.term ASC LIMIT %d',
        $status_table,
        $log_table,
        $status,
        $limit
      ), ARRAY_A);
    } elseif ($status === 'pending') {
      $rows = $wpdb->get_results($wpdb->prepare(
        'SELECT status_rows.term, status_rows.status, status_rows.source, status_rows.position, COUNT(logs.id) AS searches FROM %i AS status_rows LEFT JOIN %i AS logs ON logs.term = status_rows.term WHERE status_rows.status = %s GROUP BY status_rows.term, status_rows.status, status_rows.source, status_rows.position ORDER BY status_rows.term ASC',
        $status_table,
        $log_table,
        $status
      ), ARRAY_A);
    } elseif ($status === 'approved' && $source === 'manual' && $limit > 0) {
      $rows = $wpdb->get_results($wpdb->prepare(
        'SELECT term, status, source, position FROM %i WHERE status = %s AND source = %s ORDER BY position ASC, term ASC LIMIT %d',
        $status_table,
        $status,
        'manual',
        $limit
      ), ARRAY_A);
    } elseif ($status === 'approved' && $source === 'manual') {
      $rows = $wpdb->get_results($wpdb->prepare(
        'SELECT term, status, source, position FROM %i WHERE status = %s AND source = %s ORDER BY position ASC, term ASC',
        $status_table,
        $status,
        'manual'
      ), ARRAY_A);
    } elseif ($status === 'approved' && $source === 'not_manual' && $limit > 0) {
      $rows = $wpdb->get_results($wpdb->prepare(
        'SELECT term, status, source, position FROM %i WHERE status = %s AND source <> %s ORDER BY position ASC, term ASC LIMIT %d',
        $status_table,
        $status,
        'manual',
        $limit
      ), ARRAY_A);
    } elseif ($status === 'approved' && $source === 'not_manual') {
      $rows = $wpdb->get_results($wpdb->prepare(
        'SELECT term, status, source, position FROM %i WHERE status = %s AND source <> %s ORDER BY position ASC, term ASC',
        $status_table,
        $status,
        'manual'
      ), ARRAY_A);
    } elseif ($status === 'approved' && $limit > 0) {
      $rows = $wpdb->get_results($wpdb->prepare(
        'SELECT term, status, source, position FROM %i WHERE status = %s ORDER BY position ASC, term ASC LIMIT %d',
        $status_table,
        $status,
        $limit
      ), ARRAY_A);
    } elseif ($status === 'approved') {
      $rows = $wpdb->get_results($wpdb->prepare(
        'SELECT term, status, source, position FROM %i WHERE status = %s ORDER BY position ASC, term ASC',
        $status_table,
        $status
      ), ARRAY_A);
    } elseif ($source === 'manual' && $limit > 0) {
      $rows = $wpdb->get_results($wpdb->prepare(
        'SELECT term, status, source, position FROM %i WHERE status = %s AND source = %s ORDER BY term ASC LIMIT %d',
        $status_table,
        $status,
        'manual',
        $limit
      ), ARRAY_A);
    } elseif ($source === 'manual') {
      $rows = $wpdb->get_results($wpdb->prepare(
        'SELECT term, status, source, position FROM %i WHERE status = %s AND source = %s ORDER BY term ASC',
        $status_table,
        $status,
        'manual'
      ), ARRAY_A);
    } elseif ($source === 'not_manual' && $limit > 0) {
      $rows = $wpdb->get_results($wpdb->prepare(
        'SELECT term, status, source, position FROM %i WHERE status = %s AND source <> %s ORDER BY term ASC LIMIT %d',
        $status_table,
        $status,
        'manual',
        $limit
      ), ARRAY_A);
    } elseif ($source === 'not_manual') {
      $rows = $wpdb->get_results($wpdb->prepare(
        'SELECT term, status, source, position FROM %i WHERE status = %s AND source <> %s ORDER BY term ASC',
        $status_table,
        $status,
        'manual'
      ), ARRAY_A);
    } elseif ($limit > 0) {
      $rows = $wpdb->get_results($wpdb->prepare(
        'SELECT term, status, source, position FROM %i WHERE status = %s ORDER BY term ASC LIMIT %d',
        $status_table,
        $status,
        $limit
      ), ARRAY_A);
    } else {
      $rows = $wpdb->get_results($wpdb->prepare(
        'SELECT term, status, source, position FROM %i WHERE status = %s ORDER BY term ASC',
        $status_table,
        $status
      ), ARRAY_A);
    }

    return is_array($rows) ? $rows : array();
  }

  /**
   * Delete one term status row.
   *
   * @param string $term Search term.
   * @return void
   */
  public static function delete_term_status($term) {
    global $wpdb;

    self::ensure_status_table();

    $term = self::normalize_term($term);

    if ($term === '') {
      return;
    }

    $wpdb->delete(
      $wpdb->prefix . self::STATUS_TABLE,
      array(
        'term' => $term,
      ),
      array('%s')
    );

    if (!empty($wpdb->last_error)) {
      Log::warning(sprintf('Term status delete failed: %s', $wpdb->last_error));
    }
  }

  /**
   * Create the status table.
   *
   * @return void
   */
  private static function create_status_table() {
    global $wpdb;

    $table           = $wpdb->prefix . self::STATUS_TABLE;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "
      CREATE TABLE $table (
        term varchar(255) NOT NULL,
        status varchar(20) NOT NULL,
        source varchar(20) NOT NULL,
        position bigint(20) unsigned NOT NULL DEFAULT 0,
        PRIMARY KEY  (term),
        KEY status (status),
        KEY source (source)
      ) $charset_collate;
    ";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta($sql);
  }

  /**
   * Save every distinct logged term as pending so it still needs approval.
   *
   * @return int|bool
   */
  private static function backfill_term_status() {
    global $wpdb;

    $inserted = $wpdb->query($wpdb->prepare(
      "INSERT IGNORE INTO %i (term, status, source) SELECT DISTINCT term, 'pending', 'tracked' FROM %i WHERE term <> ''",
      $wpdb->prefix . self::STATUS_TABLE,
      $wpdb->prefix . self::TABLE_NAME
    ));

    if ($inserted === false && !empty($wpdb->last_error)) {
      Log::warning(sprintf('Term status backfill failed: %s', $wpdb->last_error));
    }

    return $inserted;
  }

  /**
   * Add the position column on tables created before ordering existed.
   *
   * @return void
   */
  private static function ensure_position_column() {
    global $wpdb;

    $table   = $wpdb->prefix . self::STATUS_TABLE;
    $columns = $wpdb->get_col($wpdb->prepare('SHOW COLUMNS FROM %i', $table));

    if (!is_array($columns) || in_array('position', $columns, true)) {
      return;
    }

    $wpdb->query($wpdb->prepare('ALTER TABLE %i ADD position bigint(20) unsigned NOT NULL DEFAULT 0', $table));
  }

  /**
   * Give added terms a position when they do not have one yet.
   *
   * @return void
   */
  private static function assign_missing_positions() {
    global $wpdb;

    $table = $wpdb->prefix . self::STATUS_TABLE;
    $zeros = $wpdb->get_col($wpdb->prepare(
      "SELECT term FROM %i WHERE status = 'approved' AND position = 0 ORDER BY term ASC",
      $table
    ));

    if (!is_array($zeros) || !$zeros) {
      return;
    }

    $max = (int) $wpdb->get_var($wpdb->prepare(
      "SELECT MAX(position) FROM %i WHERE status = 'approved'",
      $table
    ));

    foreach ($zeros as $term) {
      $max++;

      $wpdb->update(
        $table,
        array(
          'position' => $max,
        ),
        array(
          'term'   => $term,
          'status' => 'approved',
        ),
        array('%d'),
        array('%s', '%s')
      );
    }
  }

  /**
   * Normalize a term the same way the search log stores it.
   *
   * @param string $term Search term.
   * @return string
   */
  private static function normalize_term($term) {
    $term = strtolower(sanitize_text_field($term));

    if (strlen($term) > 255) {
      $term = substr($term, 0, 255);
    }

    return $term;
  }

  /**
   * Copy the old comma-separated blacklist into blacklisted terms, once.
   *
   * @return void
   */
  private static function migrate_blacklist_setting() {
    if (get_option('speedy_search_blacklist_migrated')) {
      return;
    }

    $popular = Utils::get_option('popular');

    if (!is_array($popular)) {
      update_option('speedy_search_blacklist_migrated', 1, false);
      return;
    }

    $raw   = isset($popular['blacklist']) ? $popular['blacklist'] : '';
    $words = array_filter(array_map('trim', explode(',', (string) $raw)));

    foreach ($words as $word) {
      $existing = self::get_term_status($word);
      $source   = (is_array($existing) && !empty($existing['source'])) ? $existing['source'] : 'tracked';

      self::set_term_status($word, 'rejected', $source);
    }

    if (array_key_exists('blacklist', $popular)) {
      unset($popular['blacklist']);
      Utils::update_option('popular', $popular);
    }

    update_option('speedy_search_blacklist_migrated', 1, false);
  }

}