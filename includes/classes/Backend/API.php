<?php

namespace PolyPlugins\Speedy_Search\Backend;

use PolyPlugins\Speedy_Search\TNTSearch;
use PolyPlugins\Speedy_Search\Utils;
use WP_REST_Request;
use WP_REST_Response;

class API {
  
  /**
   * Init
   *
   * @return void
   */
  public function init() {
    add_action('rest_api_init', array($this, 'add_endpoints'));
  }

  /**
	 * Add endpoint for webhooks to connect to
	 *
	 * @return void
	 */
	public function add_endpoints() {
		register_rest_route(
			'speedy-search/v1',
			'/posts/',
			array(
				'methods' => 'GET',
				'callback' => array($this, 'get_posts'),
				'permission_callback' => '__return_true',
			)
		);
	}
  
  /**
   * Get posts
   *
   * @param  mixed $request
   * @return void
   */
  public function get_posts(WP_REST_Request $request) {
		$get_result_limit = Utils::get_option('result_limit');
		$result_limit = $get_result_limit ? $get_result_limit : 10;
    $search_query = $request->get_param('search');

    if (empty($search_query)) {
      return new WP_REST_Response(array(
        'error' => 'Search query is required.'
      ), 400);
    }

    // Get TNTSearch instance
    $tnt = TNTSearch::get_instance()->tnt();
    
    $tnt->selectIndex('posts.sqlite');
    $tnt->fuzziness = true;

    // Perform the search
    $results = $tnt->search($search_query, $result_limit); // Limit to 10 results

    if (empty($results['ids'])) {
      return new WP_REST_Response([], 200); // No results found
    }

    // Fetch all posts in a single query
    $posts = get_posts(array(
      'post_type'      => 'post',
      'post__in'       => $results['ids'],
      'orderby'        => 'post__in', // Maintain order from TNTSearch
      'posts_per_page' => count($results['ids']),
    ));

    $posts_data = array();

    foreach ($posts as $post) {
      $posts_data[] = array(
        'id'        => $post->ID,
        'title'     => get_the_title($post->ID),
        'thumbnail' => get_the_post_thumbnail_url($post->ID, 'medium'),
        'excerpt'   => rtrim(substr(wp_strip_all_tags($post->post_content), 0, 150)) . '...',
        'permalink' => get_permalink($post->ID)
      );
    }

    return new WP_REST_Response($posts_data, 200);
  }

}