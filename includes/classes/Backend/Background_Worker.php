<?php

namespace PolyPlugins\Speedy_Search\Backend;

use PolyPlugins\Speedy_Search\TNTSearch;
use PolyPlugins\Speedy_Search\Utils;
use TeamTNT\TNTSearch\Exceptions\IndexNotFoundException;
use WP_Query;

class Background_Worker {

  private $tnt;
  private $options;
  private $posts_index_name    = 'posts.sqlite';
  private $pages_index_name    = 'pages.sqlite';
  private $products_index_name = 'products.sqlite';

  public function __construct() {
    $this->tnt     = TNTSearch::get_instance()->tnt();
    $this->options = Utils::get_options();
  }

  public function init() {
    add_action('background_worker', array($this, 'background_worker'));
  }

  public function background_worker() {
    $enabled               = Utils::get_option('enabled');
    $is_missing_extensions = Utils::is_missing_extensions();

    // Don't continue if missing extensions
    if ($is_missing_extensions) {
      return;
    }

    if ($enabled) {
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
    }
  }

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

          $post_id = get_the_ID();
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

}