<?php

namespace PolyPlugins\Speedy_Search\Backend;

use PolyPlugins\Speedy_Search\TNTSearch;
use PolyPlugins\Speedy_Search\Utils;
use TeamTNT\TNTSearch\Exceptions\IndexNotFoundException;
use WP_Query;

class Background_Worker {

  private $tnt;
  private $options;
  private $index_name = 'posts.sqlite';

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
      $this->maybe_index_posts();
    }
  }

  public function maybe_index_posts() {
    $posts_index          = Utils::get_index('posts');
    $is_indexing_complete = isset($posts_index['complete']) ? true : false;

    if (!$is_indexing_complete) {
      try {
        $this->tnt->selectIndex($this->index_name);
      } catch (IndexNotFoundException $e) {
        $this->tnt->createIndex($this->index_name);
        $this->tnt->selectIndex($this->index_name);
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

}