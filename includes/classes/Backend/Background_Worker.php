<?php

namespace PolyPlugins\Speedy_Search\Backend;

use PolyPlugins\Speedy_Search\TNTSearch;
use PolyPlugins\Speedy_Search\Utils;
use TeamTNT\TNTSearch\Exceptions\IndexNotFoundException;
use WP_Query;

class Background_Worker {

  private $tnt;
  private $options;
  private $posts_index_name     = 'posts.sqlite';
  private $pages_index_name     = 'pages.sqlite';
  private $products_index_name  = 'products.sqlite';
  private $downloads_index_name = 'downloads.sqlite';
  
  /**
   * __construct
   *
   * @return void
   */
  public function __construct() {
    $this->tnt     = TNTSearch::get_instance()->tnt();
    $this->options = Utils::get_options();
  }
  
  /**
   * init
   *
   * @return void
   */
  public function init() {
    add_action('snappy_search_background_worker', array($this, 'background_worker'));
    add_action('snappy_search_daily_background_worker', array($this, 'daily_background_worker'));
    add_action('cron_schedules', array($this, 'add_cron_schedules'));
  }

  /**
   * Background worker
   *
   * @return void
   */
  public function background_worker() {
    $enabled               = Utils::get_option('enabled');
    $is_missing_extensions = Utils::is_missing_extensions();

    // Don't continue if missing extensions
    if ($is_missing_extensions) {
      return;
    }

    if (!$enabled) {
      return;
    }

    $this->indexer();
  }

  /**
   * Background worker
   *
   * @return void
   */
  public function daily_background_worker() {
    $enabled = Utils::get_option('enabled');

    if (!$enabled) {
      return;
    }

    $this->cleanup_old_term_logs();
  }
  
  /**
   * Indexer
   *
   * @return void
   */
  public function indexer() {
    $posts         = Utils::get_option('posts');
    $posts_enabled = isset($posts['enabled']) ? $posts['enabled'] : 1;

    if ($posts_enabled) {
      $this->maybe_index_posts();
    }

    $pages         = Utils::get_option('pages');
    $pages_enabled = isset($pages['enabled']) ? $pages['enabled'] : 0;

    if ($pages_enabled) {
      $this->maybe_index_pages();
    }

    $products         = Utils::get_option('products');
    $products_enabled = isset($products['enabled']) ? $products['enabled'] : 0;

    if ($products_enabled && class_exists('WooCommerce')) {
      $this->maybe_index_products();
    }

    $downloads         = Utils::get_option('downloads');
    $downloads_enabled = isset($downloads['enabled']) ? $downloads['enabled'] : 0;

    if ($downloads_enabled && class_exists('Easy_Digital_Downloads')) {
      $this->maybe_index_downloads();
    }
  }

  /**
   * Cleanup old term logs
   *
   * @return void
   */
  public function cleanup_old_term_logs() {
    $popular_options = Utils::get_option('popular');
    $enabled         = isset($popular_options['enabled']) ? $popular_options['enabled'] : false;

    if (!$enabled) {
      return;
    }

    $days = isset($popular_options['days']) ? $popular_options['days'] : 30;

    DB::delete_terms_older_than_x_days($days);
  }

  /**
   * Add cron schedules
   * @param  array $schedules The schedules array
   * @return array $schedules The schedules array
   */
  public function add_cron_schedules($schedules) {
    if (!isset($schedules['every_minute'])) {
      $schedules['every_minute'] = array(
        'interval' => 60,
        'display'  => __('Every Minute', 'speedy-search')
      );
    }
    
    return $schedules;
  }

  /**
   * Maybe index posts
   *
   * @return void
   */
  public function maybe_index_posts() {
    $posts_index          = Utils::get_index('posts');
    $is_indexing_complete = isset($posts_index['complete']) ? true : false;

    if (!$is_indexing_complete) {
      try {
        $this->tnt->selectIndex($this->posts_index_name);
      } catch (IndexNotFoundException $e) {
        $this->tnt->createIndex($this->posts_index_name);
        $this->tnt->selectIndex($this->posts_index_name);
      }

      $progress = isset($posts_index['progress']) ? $posts_index['progress'] : 1;

      $args = array(
        'post_type'      => 'post', 
        'posts_per_page' => isset($this->options['posts']['batch']) ? $this->options['posts']['batch'] : 20,
        'offset'         => $progress,
        'orderby'        => 'date',
        'order'          => 'ASC',
        'post_status'    => 'publish'
      );

      $query = new WP_Query($args);

      if ($query->have_posts()) {
        $index = $this->tnt->getIndex();
        $index->disableOutput = true;

        while ($query->have_posts()) {
          $query->the_post();

          $post_id = get_the_ID();
          $title   = get_the_title();
          $content = get_the_content();

          $index->insert(array(
            'id'      => intval($post_id),
            'title'   => sanitize_text_field($title),
            'content' => sanitize_text_field($content)
          ));

          $progress++;
        }

        Utils::update_index('posts', 'progress', $progress);
      } else {
        Utils::update_index('posts', 'complete', true);
      }

      wp_reset_postdata();
    }
  }

  /**
   * Maybe index pages
   *
   * @return void
   */
  public function maybe_index_pages() {
    $pages_index          = Utils::get_index('pages');
    $is_indexing_complete = isset($pages_index['complete']) ? true : false;

    if (!$is_indexing_complete) {
      try {
        $this->tnt->selectIndex($this->pages_index_name);
      } catch (IndexNotFoundException $e) {
        $this->tnt->createIndex($this->pages_index_name);
        $this->tnt->selectIndex($this->pages_index_name);
      }

      $progress = isset($pages_index['progress']) ? $pages_index['progress'] : 1;

      $args = array(
        'post_type'      => 'page', 
        'posts_per_page' => isset($this->options['pages']['batch']) ? $this->options['pages']['batch'] : 20,
        'offset'         => $progress,
        'orderby'        => 'date',
        'order'          => 'ASC',
        'post_status'    => 'publish'
      );

      $query = new WP_Query($args);

      if ($query->have_posts()) {
        $index = $this->tnt->getIndex();
        $index->disableOutput = true;

        while ($query->have_posts()) {
          $query->the_post();

          $post_id = get_the_ID();
          $title   = get_the_title();
          $content = get_the_content();

          $index->insert(array(
            'id'      => intval($post_id),
            'title'   => sanitize_text_field($title),
            'content' => sanitize_text_field($content)
          ));

          $progress++;
        }

        Utils::update_index('pages', 'progress', $progress);
      } else {
        Utils::update_index('pages', 'complete', true);
      }

      wp_reset_postdata();
    }
  }
  
  /**
   * Maybe index products
   *
   * @return void
   */
  public function maybe_index_products() {
    $products_index       = Utils::get_index('products');
    $is_indexing_complete = isset($products_index['complete']) ? true : false;

    if (!$is_indexing_complete) {
      try {
        $this->tnt->selectIndex($this->products_index_name);
      } catch (IndexNotFoundException $e) {
        $this->tnt->createIndex($this->products_index_name);
        $this->tnt->selectIndex($this->products_index_name);
      }

      $progress = isset($products_index['progress']) ? $products_index['progress'] : 1;

      $args = array(
        'post_type'      => 'product', 
        'posts_per_page' => isset($this->options['products']['batch']) ? $this->options['products']['batch'] : 20,
        'offset'         => $progress,
        'orderby'        => 'date',
        'order'          => 'ASC',
        'post_status'    => 'publish'
      );

      $query = new WP_Query($args);

      if ($query->have_posts()) {
        $index = $this->tnt->getIndex();
        $index->disableOutput = true;

        while ($query->have_posts()) {
          $query->the_post();

          $post_id    = get_the_ID();
          $product    = wc_get_product($post_id);
          $visibility = $product->get_catalog_visibility();

          // If product does not have search visibility remove it
          if ($visibility === 'hidden' || $visibility === 'catalog') {
            continue;
          }

          $title   = get_the_title();
          $content = get_the_content();
          $sku     = get_post_meta($post_id, '_sku', true);

          $index->insert(array(
            'id'      => intval($post_id),
            'title'   => sanitize_text_field($title),
            'sku'     => sanitize_text_field($sku),
            'content' => sanitize_text_field($content)
          ));

          $progress++;
        }

        Utils::update_index('products', 'progress', $progress);
      } else {
        Utils::update_index('products', 'complete', true);
      }

      wp_reset_postdata();
    }
  }
  
  /**
   * Maybe index downloads
   *
   * @return void
   */
  public function maybe_index_downloads() {
    $downloads_index       = Utils::get_index('downloads');
    $is_indexing_complete  = isset($downloads_index['complete']) ? true : false;

    if (!$is_indexing_complete) {
      try {
        $this->tnt->selectIndex($this->downloads_index_name);
      } catch (IndexNotFoundException $e) {
        $this->tnt->createIndex($this->downloads_index_name);
        $this->tnt->selectIndex($this->downloads_index_name);
      }

      $progress = isset($downloads_index['progress']) ? $downloads_index['progress'] : 1;

      $args = array(
        'post_type'      => 'download',
        'posts_per_page' => isset($this->options['downloads']['batch']) ? $this->options['downloads']['batch'] : 20,
        'offset'         => $progress,
        'orderby'        => 'date',
        'order'          => 'ASC',
        'post_status'    => 'publish'
      );

      $query = new WP_Query($args);

      if ($query->have_posts()) {
        $index = $this->tnt->getIndex();
        $index->disableOutput = true;

        while ($query->have_posts()) {
          $query->the_post();

          $post_id = get_the_ID();
          $title   = get_the_title();
          $content = get_the_content();

          $index->insert(array(
            'id'      => intval($post_id),
            'title'   => sanitize_text_field($title),
            'content' => sanitize_text_field($content)
          ));

          $progress++;
        }

        Utils::update_index('downloads', 'progress', $progress);
      } else {
        Utils::update_index('downloads', 'complete', true);
      }

      wp_reset_postdata();
    }
  }

}