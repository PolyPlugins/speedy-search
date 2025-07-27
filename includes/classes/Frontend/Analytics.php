<?php

namespace PolyPlugins\Speedy_Search\Frontend;

use PolyPlugins\Speedy_Search\Backend\DB;
use PolyPlugins\Speedy_Search\Utils;

class Analytics {

  private $plugin;
  private $version;
  private $plugin_dir_url;

  public function __construct($plugin, $version, $plugin_dir_url) {
    $this->plugin         = $plugin;
    $this->version        = $version;
    $this->plugin_dir_url = $plugin_dir_url;
  }

  public function init() {
    add_action('wp_ajax_speedy_search_query', array($this, 'track_query'));
  }

  public function track_query() {
    $popular_options = Utils::get_option('popular');
    $popular_enabled = isset($popular_options['enabled']) ? $popular_options['enabled'] : 0;

    if (!$popular_enabled) {
      return;
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'speedy_search_analytics_nonce')) {
      Utils::send_error('Invalid session', 403);
    }

    $term = isset($_POST['term']) ? sanitize_text_field(wp_unslash($_POST['term'])) : '';

    if (!$term) {
      return;
    }

    $result_counts = isset($_POST['result_counts']) && is_array($_POST['result_counts']) ? array_map('absint', wp_unslash($_POST['result_counts'])) : array();
    
    if (!$result_counts) {
      return;
    }
    
    foreach($result_counts as $type => $result_count) {
      $type = $type ? sanitize_text_field($type) : '';

      if (!$type) {
        continue;
      }

      $allowed_types = Utils::get_allowed_post_types();

      if (!in_array($type, $allowed_types)) {
        continue;
      }

      if (!is_numeric($result_count) && $result_count >= 0) {
        continue;
      }

      DB::insert_term_log($term, $type, $result_count);
    }

    Utils::send_success('Success');
  }

}
