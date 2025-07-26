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
    add_action('rest_api_init', array($this, 'add_new_endpoints'));
  }

  /**
	 * Add endpoint for webhooks to connect to
	 *
	 * @return void
	 */
	public function add_endpoints() {
    $products         = Utils::get_option('products');
    $products_enabled = isset($products['enabled']) ? $products['enabled'] : 0;

    if ($products_enabled) {
      register_rest_route(
        'speedy-search/v1',
        '/products/',
        array(
          'methods' => 'GET',
          'callback' => array($this, 'get_products'),
          'permission_callback' => '__return_true',
        )
      );
    }

    $downloads         = Utils::get_option('downloads');
    $downloads_enabled = isset($downloads['enabled']) ? $downloads['enabled'] : 0;

    if ($downloads_enabled) {
      register_rest_route(
        'speedy-search/v1',
        '/downloads/',
        array(
          'methods' => 'GET',
          'callback' => array($this, 'get_downloads'),
          'permission_callback' => '__return_true',
        )
      );
    }

    $posts         = Utils::get_option('posts');
    $posts_enabled = isset($posts['enabled']) ? $posts['enabled'] : 1;

    if ($posts_enabled) {
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

    $pages         = Utils::get_option('pages');
    $pages_enabled = isset($pages['enabled']) ? $pages['enabled'] : 0;

    if ($pages_enabled) {
      register_rest_route(
        'speedy-search/v1',
        '/pages/',
        array(
          'methods' => 'GET',
          'callback' => array($this, 'get_pages'),
          'permission_callback' => '__return_true',
        )
      );
	  }
	}

  /**
	 * Add endpoint for webhooks to connect to
	 *
	 * @return void
	 */
	public function add_new_endpoints() {
    $products         = Utils::get_option('products');
    $products_enabled = isset($products['enabled']) ? $products['enabled'] : 0;

    if ($products_enabled) {
      register_rest_route(
        'snappy-search/v1',
        '/products/',
        array(
          'methods' => 'GET',
          'callback' => array($this, 'get_products'),
          'permission_callback' => '__return_true',
        )
      );
    }

    $downloads         = Utils::get_option('downloads');
    $downloads_enabled = isset($downloads['enabled']) ? $downloads['enabled'] : 0;

    if ($downloads_enabled) {
      register_rest_route(
        'snappy-search/v1',
        '/downloads/',
        array(
          'methods' => 'GET',
          'callback' => array($this, 'get_downloads'),
          'permission_callback' => '__return_true',
        )
      );
    }

    $posts         = Utils::get_option('posts');
    $posts_enabled = isset($posts['enabled']) ? $posts['enabled'] : 1;

    if ($posts_enabled) {
      register_rest_route(
        'snappy-search/v1',
        '/posts/',
        array(
          'methods' => 'GET',
          'callback' => array($this, 'get_posts'),
          'permission_callback' => '__return_true',
        )
      );
    }

    $pages         = Utils::get_option('pages');
    $pages_enabled = isset($pages['enabled']) ? $pages['enabled'] : 0;

    if ($pages_enabled) {
      register_rest_route(
        'snappy-search/v1',
        '/pages/',
        array(
          'methods' => 'GET',
          'callback' => array($this, 'get_pages'),
          'permission_callback' => '__return_true',
        )
      );
	  }
	}
  
  /**
   * Get posts
   *
   * @param  mixed $request
   * @return void
   */
  public function get_posts(WP_REST_Request $request) {
		$options          = Utils::get_option('posts');
		$result_limit     = isset($options['result_limit']) ? $options['result_limit'] : 10;
		$max_characters   = isset($options['max_characters']) ? $options['max_characters'] : 100;
    $get_search_query = $request->get_param('search');
    $search_query     = $get_search_query ? sanitize_text_field($get_search_query) : '';

    if (empty($search_query)) {
      return new WP_REST_Response(array(
        'error' => 'Search query is required.'
      ), 400);
    }

    if (strlen($search_query) > $max_characters) {
      return new WP_REST_Response(array(
        'error' => 'Too many characters in search query.'
      ), 400);
    }

    // Generate a unique cache key for this search
    $cache_key = 'speedy_search_' . md5($search_query);

    // Check if results exist in WordPress Object Cache
    $cached_results = wp_cache_get($cache_key);

    if ($cached_results !== false) {
      return new WP_REST_Response($cached_results, 200);
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
    if (!$cached_results) {
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

      wp_cache_set($cache_key, $posts_data, '', 600);
      $cached_results = wp_cache_get($cache_key);

      return new WP_REST_Response($posts_data, 200);
    }
  }
  
  /**
   * Get pages
   *
   * @param  mixed $request
   * @return void
   */
  public function get_pages(WP_REST_Request $request) {
		$options          = Utils::get_option('pages');
		$result_limit     = isset($options['result_limit']) ? $options['result_limit'] : 10;
		$max_characters   = isset($options['max_characters']) ? $options['max_characters'] : 100;
    $get_search_query = $request->get_param('search');
    $search_query     = $get_search_query ? sanitize_text_field($get_search_query) : '';

    if (empty($search_query)) {
      return new WP_REST_Response(array(
        'error' => 'Search query is required.'
      ), 400);
    }

    if (strlen($search_query) > $max_characters) {
      return new WP_REST_Response(array(
        'error' => 'Too many characters in search query.'
      ), 400);
    }

    // Generate a unique cache key for this search
    $cache_key = 'speedy_search_' . md5($search_query);

    // Check if results exist in WordPress Object Cache
    $cached_results = wp_cache_get($cache_key);

    if ($cached_results !== false) {
      return new WP_REST_Response($cached_results, 200);
    }

    // Get TNTSearch instance
    $tnt = TNTSearch::get_instance()->tnt();
    
    $tnt->selectIndex('pages.sqlite');
    $tnt->fuzziness = true;

    // Perform the search
    $results = $tnt->search($search_query, $result_limit); // Limit to 10 results

    if (empty($results['ids'])) {
      return new WP_REST_Response([], 200); // No results found
    }

    // Fetch all posts in a single query
    if (!$cached_results) {
      $posts = get_posts(array(
        'post_type'      => 'page',
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

      wp_cache_set($cache_key, $posts_data, '', 600);
      $cached_results = wp_cache_get($cache_key);

      return new WP_REST_Response($posts_data, 200);
    }
  }
  
  /**
   * Get products
   *
   * @param  mixed $request
   * @return void
   */
  public function get_products(WP_REST_Request $request) {
		$options          = Utils::get_option('products');
		$result_limit     = isset($options['result_limit']) ? $options['result_limit'] : 10;
		$max_characters   = isset($options['max_characters']) ? $options['max_characters'] : 100;
    $get_search_query = $request->get_param('search');
    $search_query     = $get_search_query ? sanitize_text_field($get_search_query) : '';

    if (empty($search_query)) {
      return new WP_REST_Response(array(
        'error' => 'Search query is required.'
      ), 400);
    }

    if (strlen($search_query) > $max_characters) {
      return new WP_REST_Response(array(
        'error' => 'Too many characters in search query.'
      ), 400);
    }

    // Generate a unique cache key for this search
    $cache_key = 'speedy_search_' . md5($search_query);

    // Check if results exist in WordPress Object Cache
    $cached_results = wp_cache_get($cache_key);

    if ($cached_results !== false) {
      return new WP_REST_Response($cached_results, 200);
    }

    // Get TNTSearch instance
    $tnt = TNTSearch::get_instance()->tnt();
    
    $tnt->selectIndex('products.sqlite');
    $tnt->fuzziness = true;

    // Perform the search
    $results = $tnt->search($search_query, $result_limit); // Limit to 10 results

    if (empty($results['ids'])) {
      return new WP_REST_Response([], 200); // No results found
    }

    // Fetch all posts in a single query
    if (!$cached_results) {
      $posts = get_posts(array(
        'post_type'      => 'product',
        'post__in'       => $results['ids'],
        'orderby'        => 'post__in', // Maintain order from TNTSearch
        'posts_per_page' => count($results['ids']),
      ));

      $posts_data = array();

      foreach ($posts as $post) {
        $posts_data[] = array(
          'id'        => $post->ID,
          'title'     => get_the_title($post->ID),
          'price'     => get_post_meta($post->ID, '_price'),
          'thumbnail' => get_the_post_thumbnail_url($post->ID, 'medium'),
          'excerpt'   => rtrim(substr(wp_strip_all_tags($post->post_content), 0, 150)) . '...',
          'permalink' => get_permalink($post->ID)
        );
      }

      wp_cache_set($cache_key, $posts_data, '', 600);
      $cached_results = wp_cache_get($cache_key);

      return new WP_REST_Response($posts_data, 200);
    }
  }
  
  /**
   * Get downloads
   *
   * @param  mixed $request
   * @return void
   */
  public function get_downloads(WP_REST_Request $request) {
		$options          = Utils::get_option('downloads');
		$result_limit     = isset($options['result_limit']) ? $options['result_limit'] : 10;
		$max_characters   = isset($options['max_characters']) ? $options['max_characters'] : 100;
    $get_search_query = $request->get_param('search');
    $search_query     = $get_search_query ? sanitize_text_field($get_search_query) : '';

    if (empty($search_query)) {
      return new WP_REST_Response(array(
        'error' => 'Search query is required.'
      ), 400);
    }

    if (strlen($search_query) > $max_characters) {
      return new WP_REST_Response(array(
        'error' => 'Too many characters in search query.'
      ), 400);
    }

    // Generate a unique cache key for this search
    $cache_key = 'speedy_search_' . md5($search_query);

    // Check if results exist in WordPress Object Cache
    $cached_results = wp_cache_get($cache_key);

    if ($cached_results !== false) {
      return new WP_REST_Response($cached_results, 200);
    }

    // Get TNTSearch instance
    $tnt = TNTSearch::get_instance()->tnt();
    
    $tnt->selectIndex('downloads.sqlite');
    $tnt->fuzziness = true;

    // Perform the search
    $results = $tnt->search($search_query, $result_limit); // Limit to 10 results

    if (empty($results['ids'])) {
      return new WP_REST_Response([], 200); // No results found
    }

    // Fetch all posts in a single query
    if (!$cached_results) {
      $posts = get_posts(array(
        'post_type'      => 'download',
        'post__in'       => $results['ids'],
        'orderby'        => 'post__in', // Maintain order from TNTSearch
        'posts_per_page' => count($results['ids']),
      ));

      $posts_data = array();

      foreach ($posts as $post) {
        $price = get_post_meta($post->ID, 'edd_price', true);

        $posts_data[] = array(
          'id'        => $post->ID,
          'title'     => get_the_title($post->ID),
          'price'     => is_array($price) ? sanitize_text_field(implode(', ', $price)) : sanitize_text_field($price),
          'thumbnail' => get_the_post_thumbnail_url($post->ID, 'medium'),
          'excerpt'   => rtrim(substr(wp_strip_all_tags($post->post_content), 0, 150)) . '...',
          'permalink' => get_permalink($post->ID)
        );
      }

      wp_cache_set($cache_key, $posts_data, '', 600);
      $cached_results = wp_cache_get($cache_key);

      return new WP_REST_Response($posts_data, 200);
    }
  }

}