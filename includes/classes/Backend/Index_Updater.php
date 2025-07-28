<?php

namespace PolyPlugins\Speedy_Search\Backend;

use PolyPlugins\Speedy_Search\TNTSearch;
use PolyPlugins\Speedy_Search\Utils;

class Index_Updater {

  private $plugin;
  private $version;
  private $plugin_dir_url;
  private $index_path;
  private $tnt;

  public function __construct($plugin, $version, $plugin_dir_url) {
    $this->index_path     = TNTSearch::get_instance()->get_index_path();
    $this->plugin         = $plugin;
    $this->version        = $version;
    $this->plugin_dir_url = $plugin_dir_url;
    $this->tnt            = TNTSearch::get_instance()->tnt();
  }

  public function init() {
    add_action('save_post', array($this, 'update_index'), 10, 3);
    add_action('delete_post', array($this, 'remove_from_index'));
    add_action('transition_post_status', array($this, 'handle_status_transition'), 10, 3);
  }

  /**
   * Update or add post/page/product/download to the index
   *
   * @param int     $post_id
   * @param WP_Post $post
   * @param bool    $update
   * @return void
   */
  public function update_index($post_id, $post, $update) {
    // Only index published posts
    if ($post->post_status !== 'publish') {
      // If post is no longer published, remove from index
      $this->remove_from_index($post_id);
      return;
    }

    $post_type = $post->post_type;

    // Allowed post types for indexing
    $allowed_types = Utils::get_allowed_post_types();

    if (!in_array($post_type, $allowed_types, true)) {
      return;
    }

    // Select or create the appropriate index
    $index_name = Utils::get_index_name($post_type);

    try {
      $this->tnt->selectIndex($index_name);
    } catch (\TeamTNT\TNTSearch\Exceptions\IndexNotFoundException $e) {
      $this->tnt->createIndex($index_name);
      $this->tnt->selectIndex($index_name);
    }

    $index = $this->tnt->getIndex();
    $index->disableOutput = true;

    // Prepare data for indexing
    $title   = sanitize_text_field($post->post_title);
    $content = sanitize_text_field($post->post_content);

    $data = array(
      'id'      => intval($post_id),
      'title'   => $title,
      'content' => $content,
    );

    if ($post_type === 'product' && class_exists('WooCommerce')) {
      $sku = get_post_meta($post_id, '_sku', true);
      $data['sku'] = sanitize_text_field($sku);
    }

    $index->insert($data);
  }

  /**
   * Remove a post/page/product/download from the index when deleted or unpublished
   *
   * @param int $post_id
   * @return void
   */
  public function remove_from_index($post_id) {
    $post = get_post($post_id);

    if (!$post) {
      return;
    }

    $post_type = $post->post_type;

    $allowed_types = Utils::get_allowed_post_types();

    if (!in_array($post_type, $allowed_types, true)) {
      return;
    }

    $index_name = Utils::get_index_name($post_type);

    try {
      $this->tnt->selectIndex($index_name);
    } catch (\TeamTNT\TNTSearch\Exceptions\IndexNotFoundException $e) {
      return;
    }

    $index = $this->tnt->getIndex();
    $index->disableOutput = true;

    $index->delete($post_id);
  }

  /**
   * Handle post status changes for indexing
   *
   * @param string $new_status
   * @param string $old_status
   * @param WP_Post $post
   * @return void
   */
  public function handle_status_transition($new_status, $old_status, $post) {
    if ($old_status == $new_status) {
      return;
    }

    $allowed_types = Utils::get_allowed_post_types();

    if (!in_array($post->post_type, $allowed_types, true)) {
      return;
    }

    // If going from any status TO publish — add/update index
    if ($new_status === 'publish') {
      // If type is product
      if (get_post_type($post->ID) === 'product') {
        $product    = wc_get_product($post->ID);
        $visibility = $product->get_catalog_visibility();

        // If product does not have search visibility remove it
        if ($visibility === 'hidden' || $visibility === 'catalog') {
          $this->remove_from_index($post->ID);
        }
      } else {
        $this->update_index($post->ID, $post, true);
      }
    }

    // If going FROM publish TO any other status — remove from index
    if ($new_status !== 'publish') {
      $this->remove_from_index($post->ID);
    }
  }
}
