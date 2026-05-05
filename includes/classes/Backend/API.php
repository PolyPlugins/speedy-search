<?php

namespace PolyPlugins\Speedy_Search\Backend;

use PolyPlugins\Speedy_Search\TNTSearch;
use PolyPlugins\Speedy_Search\Utils;
use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) exit;

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

    if ($posts_enabled || $pages_enabled || $products_enabled || $downloads_enabled) {
      register_rest_route(
        'speedy-search/v1',
        '/search/',
        array(
          'methods' => 'GET',
          'callback' => array($this, 'get_search'),
          'permission_callback' => '__return_true',
        )
      );

      register_rest_route(
        'speedy-search/v1',
        '/preload/',
        array(
          'methods' => 'GET',
          'callback' => array($this, 'get_preload'),
          'permission_callback' => '__return_true',
        )
      );

      register_rest_route(
        'speedy-search/v1',
        '/latest/',
        array(
          'methods' => 'GET',
          'callback' => array($this, 'get_latest'),
          'permission_callback' => '__return_true',
        )
      );
    }
    
    $orders         = Utils::get_option('orders');
    $orders_enabled = isset($orders['enabled']) ? $orders['enabled'] : 0;

    if ($orders_enabled) {
      register_rest_route(
        'speedy-search-search/v1',
        '/orders/',
        array(
          'methods' => 'GET',
          'callback' => array($this, 'get_orders'),
          'permission_callback' => array($this, 'check_permissions')
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

    if ($posts_enabled || $pages_enabled || $products_enabled || $downloads_enabled) {
      register_rest_route(
        'snappy-search/v1',
        '/search/',
        array(
          'methods' => 'GET',
          'callback' => array($this, 'get_search'),
          'permission_callback' => '__return_true',
        )
      );

      register_rest_route(
        'snappy-search/v1',
        '/preload/',
        array(
          'methods' => 'GET',
          'callback' => array($this, 'get_preload'),
          'permission_callback' => '__return_true',
        )
      );

      register_rest_route(
        'snappy-search/v1',
        '/latest/',
        array(
          'methods' => 'GET',
          'callback' => array($this, 'get_latest'),
          'permission_callback' => '__return_true',
        )
      );
    }
	}

  /**
   * Get combined search results
   *
   * @param  mixed $request
   * @return void
   */
  public function get_search(WP_REST_Request $request) {
    $get_search_query = $request->get_param('search');
    $search_query     = $get_search_query ? sanitize_text_field($get_search_query) : '';
    $expanded_query   = $this->expand_search_query($search_query);
    $search_key       = strtolower(trim($expanded_query));
    $posts_title_only_search      = $this->is_title_only_search_enabled('posts');
    $pages_title_only_search      = $this->is_title_only_search_enabled('pages');
    $products_title_only_search   = $this->is_title_only_search_enabled('products');
    $downloads_title_only_search  = $this->is_title_only_search_enabled('downloads');
    $posts_boolean_search         = $this->is_boolean_search_enabled('posts');
    $pages_boolean_search         = $this->is_boolean_search_enabled('pages');
    $products_boolean_search      = $this->is_boolean_search_enabled('products');
    $downloads_boolean_search     = $this->is_boolean_search_enabled('downloads');
    $products_options_for_cache   = Utils::get_option('products');
    $products_top_sellers_first   = !empty($products_options_for_cache['top_sellers_first']);
    $products_sort_by_rating      = !isset($products_options_for_cache['sort_by_rating']) || !empty($products_options_for_cache['sort_by_rating']);
    $cache_key        = 'speedy_search_combined_' . md5($search_key . '|' . (int) $posts_title_only_search . '|' . (int) $pages_title_only_search . '|' . (int) $products_title_only_search . '|' . (int) $downloads_title_only_search . '|' . (int) $posts_boolean_search . '|' . (int) $pages_boolean_search . '|' . (int) $products_boolean_search . '|' . (int) $downloads_boolean_search . '|' . (int) $products_top_sellers_first . '|' . (int) $products_sort_by_rating);
    $cached_results   = Utils::get_api_cache($cache_key);

    if ($cached_results !== false) {
      return new WP_REST_Response($cached_results, 200);
    }

    $results = array();
    $types   = array(
      'posts' => array(
        'option' => 'posts',
        'method' => 'get_posts',
      ),
      'pages' => array(
        'option' => 'pages',
        'method' => 'get_pages',
      ),
      'products' => array(
        'option' => 'products',
        'method' => 'get_products',
      ),
      'downloads' => array(
        'option' => 'downloads',
        'method' => 'get_downloads',
      ),
    );

    foreach ($types as $type => $args) {
      $options = Utils::get_option($args['option']);
      $enabled = isset($options['enabled']) ? $options['enabled'] : false;

      if (!$enabled) {
        $results[$type] = array();
        continue;
      }

      $response = call_user_func(array($this, $args['method']), $request);

      if ($response instanceof WP_REST_Response) {
        if ($response->get_status() >= 400) {
          return $response;
        }

        $results[$type] = $response->get_data();
      } else {
        $results[$type] = array();
      }
    }

    Utils::set_api_cache($cache_key, $results, 600);

    return new WP_REST_Response($results, 200);
  }

  /**
   * Get preload data for empty search state
   *
   * @param WP_REST_Request $request
   * @return WP_REST_Response
   */
  public function get_preload(WP_REST_Request $request) {
    return $this->get_latest($request);
  }

  /**
   * Get latest data for empty search state
   *
   * @param WP_REST_Request $request
   * @return WP_REST_Response
   */
  public function get_latest(WP_REST_Request $request) {
    $cache_key      = 'speedy_search_latest_v3';
    $cached_results = Utils::get_api_cache($cache_key);

    if ($cached_results !== false) {
      return new WP_REST_Response($cached_results, 200);
    }

    $results = $this->get_latest_enabled_results();

    Utils::set_api_cache($cache_key, $results, 600);

    return new WP_REST_Response($results, 200);
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
    $title_only_search  = $this->is_title_only_search_enabled('posts');
    $boolean_search     = $this->is_boolean_search_enabled('posts');
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

    $expanded_query = $this->expand_search_query($search_query);

    // Generate a unique cache key for this search
    $cache_key = 'speedy_search_posts_' . md5($expanded_query . '|' . (int) $title_only_search . '|' . (int) $boolean_search);

    // Check if results exist in WordPress Object Cache
    $cached_results = Utils::get_api_cache($cache_key);

    if ($cached_results !== false) {
      return new WP_REST_Response($cached_results, 200);
    }

    // Get TNTSearch instance
    $tnt = TNTSearch::get_instance()->tnt();

    $index_name = Utils::get_index_name('post');

    $tnt->selectIndex($index_name);
    $tnt->fuzziness = true;

    // Perform the search
    $results = $this->perform_tnt_search($tnt, $expanded_query, $result_limit, $boolean_search, $search_query);

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
        $title = get_the_title($post->ID);

        if ($title_only_search && !$this->title_contains_search_terms($title, $search_query)) {
          continue;
        }

        $posts_data[] = array(
          'id'        => $post->ID,
          'title'     => $title,
          'thumbnail' => get_the_post_thumbnail_url($post->ID, 'medium'),
          'excerpt'   => $this->get_excerpt($post->post_content),
          'permalink' => get_permalink($post->ID)
        );
      }

      Utils::set_api_cache($cache_key, $posts_data, 600);

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
    $title_only_search = $this->is_title_only_search_enabled('pages');
    $boolean_search    = $this->is_boolean_search_enabled('pages');
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

    $expanded_query = $this->expand_search_query($search_query);

    // Generate a unique cache key for this search
    $cache_key = 'speedy_search_pages_' . md5($expanded_query . '|' . (int) $title_only_search . '|' . (int) $boolean_search);

    // Check if results exist in WordPress Object Cache
    $cached_results = Utils::get_api_cache($cache_key);

    if ($cached_results !== false) {
      return new WP_REST_Response($cached_results, 200);
    }

    // Get TNTSearch instance
    $tnt = TNTSearch::get_instance()->tnt();
    
    $index_name = Utils::get_index_name('page');

    $tnt->selectIndex($index_name);
    $tnt->fuzziness = true;

    // Perform the search
    $results = $this->perform_tnt_search($tnt, $expanded_query, $result_limit, $boolean_search, $search_query);

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
        $title = get_the_title($post->ID);

        if ($title_only_search && !$this->title_contains_search_terms($title, $search_query)) {
          continue;
        }

        $posts_data[] = array(
          'id'        => $post->ID,
          'title'     => $title,
          'thumbnail' => get_the_post_thumbnail_url($post->ID, 'medium'),
          'excerpt'   => $this->get_excerpt($post->post_content),
          'permalink' => get_permalink($post->ID)
        );
      }

      Utils::set_api_cache($cache_key, $posts_data, 600);

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
    $out_of_stock_last = isset($options['out_of_stock_last']) ? (bool) $options['out_of_stock_last'] : false;
    $top_sellers_first = !empty($options['top_sellers_first']);
    $sort_by_rating    = !isset($options['sort_by_rating']) || !empty($options['sort_by_rating']);
    $title_only_search = $this->is_title_only_search_enabled('products');
    $boolean_search    = $this->is_boolean_search_enabled('products');
    $custom_fields_raw = Utils::get_option('filters_custom_fields');
    $custom_fields     = $custom_fields_raw ? array_filter(array_map('trim', explode(',', $custom_fields_raw))) : array();
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

    $expanded_query = $this->expand_search_query($search_query);

    // Generate a unique cache key for this search
    $cache_key = 'speedy_search_products_' . md5($expanded_query . '|' . (int) $out_of_stock_last . '|' . (int) $title_only_search . '|' . (int) $boolean_search . '|' . (int) $top_sellers_first . '|' . (int) $sort_by_rating);

    // Check if results exist in WordPress Object Cache
    $cached_results = Utils::get_api_cache($cache_key);

    if ($cached_results !== false) {
      return new WP_REST_Response($cached_results, 200);
    }

    // Get TNTSearch instance
    $tnt = TNTSearch::get_instance()->tnt();
    
    $index_name = Utils::get_index_name('product');
    
    $tnt->selectIndex($index_name);
    $tnt->fuzziness = true;

    // Perform the search
    $results = $this->perform_tnt_search($tnt, $expanded_query, $result_limit, $boolean_search, $search_query);

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

      $posts_data    = array();
      $relevance_idx = 0;

      foreach ($posts as $post) {
        $product = wc_get_product($post->ID);
        $title   = get_the_title($post->ID);

        if ($product && $product->get_catalog_visibility() === 'hidden') {
          continue;
        }

        if ($title_only_search && !$this->title_contains_search_terms($title, $search_query)) {
          continue;
        }

        $average_rating = get_post_meta($post->ID, '_wc_average_rating', true) ?: 0;
        $is_featured = $product ? $product->is_featured() : false;
        $is_variable = $product ? $product->is_type('variable') : false;
        $is_in_stock = $product ? $product->is_in_stock() : true;
        $add_to_cart_url = $product ? $product->add_to_cart_url() : get_permalink($post->ID);
        $product_custom_fields = array();

        $tags = array(
          'div'  => array('class' => array(), 'role' => array(), 'aria-label' => array()),
          'span' => array('class' => array(), 'style' => array()),
        );

        if (!empty($custom_fields)) {
          foreach ($custom_fields as $custom_field) {
            $meta_key   = sanitize_key($custom_field);
            $meta_values = $this->get_product_custom_field_values($post->ID, $meta_key, $product);
            $product_custom_fields[$meta_key] = $meta_values;
          }
        }
        
        $data = array(
          'id'             => $post->ID,
          'title'          => $title,
          'price'          => get_post_meta($post->ID, '_price', true),
          'thumbnail'      => get_the_post_thumbnail_url($post->ID, 'medium'),
          'excerpt'        => $this->get_excerpt($post->post_content),
          'average_rating' => $average_rating ? (float) $average_rating : 0,
          'rating'         => $average_rating ? wp_kses(wc_get_rating_html((float) $average_rating), $tags) : wp_kses('<div class="star-rating"><span style="width:0%">No rating</span></div>', $tags),
          'is_featured'    => (bool) $is_featured,
          'is_variable'    => (bool) $is_variable,
          'is_in_stock'    => (bool) $is_in_stock,
          'add_to_cart_url' => esc_url_raw($add_to_cart_url),
          'permalink'      => get_permalink($post->ID),
          'custom_fields'  => $product_custom_fields,
          'title_match'      => $this->title_contains_search_terms(get_the_title($post->ID), $search_query),
          'total_sales'      => (int) get_post_meta($post->ID, '_total_sales', true),
          'relevance_order'  => $relevance_idx,
        );
        $posts_data[] = $data;
        $relevance_idx++;
      }

      usort($posts_data, function($a, $b) use ($out_of_stock_last, $top_sellers_first, $sort_by_rating) {
        if ($out_of_stock_last && $a['is_in_stock'] !== $b['is_in_stock']) {
          return $a['is_in_stock'] ? -1 : 1;
        }

        if ($a['is_featured'] !== $b['is_featured']) {
          return $a['is_featured'] ? -1 : 1;
        }

        if ($a['title_match'] !== $b['title_match']) {
          return $a['title_match'] ? -1 : 1;
        }

        if ($top_sellers_first) {
          $by_sales = $b['total_sales'] <=> $a['total_sales'];

          if ($by_sales !== 0) {
            return $by_sales;
          }
        }

        if ($sort_by_rating) {
          $by_rating = $b['average_rating'] <=> $a['average_rating'];

          if ($by_rating !== 0) {
            return $by_rating;
          }
        }

        return $a['relevance_order'] <=> $b['relevance_order'];
      });

      foreach ($posts_data as $index => $item) {
        unset($posts_data[$index]['title_match']);
        unset($posts_data[$index]['total_sales']);
        unset($posts_data[$index]['relevance_order']);
      }

      Utils::set_api_cache($cache_key, $posts_data, 600);

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
    $title_only_search = $this->is_title_only_search_enabled('downloads');
    $boolean_search    = $this->is_boolean_search_enabled('downloads');
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

    $expanded_query = $this->expand_search_query($search_query);

    // Generate a unique cache key for this search
    $cache_key = 'speedy_search_downloads_' . md5($expanded_query . '|' . (int) $title_only_search . '|' . (int) $boolean_search);

    // Check if results exist in WordPress Object Cache
    $cached_results = Utils::get_api_cache($cache_key);

    if ($cached_results !== false) {
      return new WP_REST_Response($cached_results, 200);
    }

    // Get TNTSearch instance
    $tnt = TNTSearch::get_instance()->tnt();
    
    $index_name = Utils::get_index_name('download');

    $tnt->selectIndex($index_name);
    $tnt->fuzziness = true;

    // Perform the search
    $results = $this->perform_tnt_search($tnt, $expanded_query, $result_limit, $boolean_search, $search_query);

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
        $title = get_the_title($post->ID);

        if ($title_only_search && !$this->title_contains_search_terms($title, $search_query)) {
          continue;
        }

        $posts_data[] = array(
          'id'        => $post->ID,
          'title'     => $title,
          'price'     => is_array($price) ? sanitize_text_field(implode(', ', $price)) : sanitize_text_field($price),
          'thumbnail' => get_the_post_thumbnail_url($post->ID, 'medium'),
          'excerpt'   => $this->get_excerpt($post->post_content),
          'permalink' => get_permalink($post->ID)
        );
      }

      Utils::set_api_cache($cache_key, $posts_data, 600);

      return new WP_REST_Response($posts_data, 200);
    }
  }

  /**
   * Get orders
   *
   * @param  mixed $request
   * @return void
   */
  public function get_orders(WP_REST_Request $request) {
		$options          = Utils::get_option('orders');
		$result_limit     = isset($options['result_limit']) ? $options['result_limit'] : 100;
		$max_characters   = isset($options['max_characters']) ? $options['max_characters'] : 100;
    $boolean_search   = $this->is_boolean_search_enabled('orders');
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

    $expanded_query = $this->expand_search_query($search_query);

    // Generate a unique cache key for this search
    $cache_key = 'speedy_search_orders_' . md5($expanded_query . '|' . (int) $boolean_search);

    // Check if results exist in WordPress Object Cache
    $cached_results = Utils::get_api_cache($cache_key);

    if ($cached_results !== false) {
      return new WP_REST_Response($cached_results, 200);
    }

    // Get TNTSearch instance
    $tnt = TNTSearch::get_instance()->tnt();
    
    $index_name = Utils::get_index_name('shop_order');
    
    $tnt->selectIndex($index_name);
    $tnt->fuzziness = true;

    // Perform the search
    $results    = $this->perform_tnt_search($tnt, $expanded_query, $result_limit, $boolean_search, $search_query);
    $result_ids = isset($results['ids']) ? array_map('absint', $results['ids']) : array();

    if (empty($result_ids)) {
      return new WP_REST_Response([], 200); // No results found
    }

    $orders_data = array();

    foreach ($result_ids as $order_id) {
      $order = wc_get_order($order_id);

      $orders_data[] = array(
        'id'                  => $order->get_id(),
        'order_number'        => sanitize_text_field($order->get_order_number()),
        'order_date'          => sanitize_text_field($order->get_date_created()->date('Y-m-d H:i:s')),
        'billing_first_name'  => sanitize_text_field($order->get_billing_first_name()),
        'billing_last_name'   => sanitize_text_field($order->get_billing_last_name()),
        'billing_address_1'   => sanitize_text_field($order->get_billing_address_1()),
        'billing_address_2'   => sanitize_text_field($order->get_billing_address_2()),
        'billing_city'        => sanitize_text_field($order->get_billing_city()),
        'billing_email'       => sanitize_email($order->get_billing_email()),
        'billing_phone'       => sanitize_text_field($order->get_billing_phone()),
        'shipping_first_name' => sanitize_text_field($order->get_shipping_first_name()),
        'shipping_last_name'  => sanitize_text_field($order->get_shipping_last_name()),
        'shipping_address_1'  => sanitize_text_field($order->get_shipping_address_1()),
        'shipping_address_2'  => sanitize_text_field($order->get_shipping_address_2()),
        'shipping_city'       => sanitize_text_field($order->get_shipping_city()),
        'order_status'        => sanitize_text_field($order->get_status()),
        'total'               => sanitize_text_field($order->get_total()),
        'origin'              => sanitize_text_field($order->get_meta('_wc_order_attribution_utm_source')),
      );
    }

    Utils::set_api_cache($cache_key, $orders_data, 600);

    return new WP_REST_Response($orders_data, 200);
  }

  public function check_permissions() {
    return current_user_can('manage_woocommerce');
  }

  /**
   * Expand query with configured synonyms.
   *
   * @param string $search_query
   * @return string
   */
  private function expand_search_query($search_query) {
    $search_query = sanitize_text_field((string) $search_query);
    $normalized   = strtolower(trim($search_query));

    if ($normalized === '') {
      return $search_query;
    }

    $synonym_map = $this->get_synonym_map();
    $additions   = array();

    if (isset($synonym_map[$normalized])) {
      $additions[] = $synonym_map[$normalized];
    }

    $tokens = preg_split('/\s+/', $normalized);

    if (is_array($tokens)) {
      foreach ($tokens as $token) {
        if ($token === '' || !isset($synonym_map[$token])) {
          continue;
        }

        $additions[] = $synonym_map[$token];
      }
    }

    $additions = array_filter(array_unique(array_map('trim', $additions)));

    if (empty($additions)) {
      return $search_query;
    }

    return trim($search_query . ' ' . implode(' ', $additions));
  }

  /**
   * Build synonym map from settings.
   *
   * @return array
   */
  private function get_synonym_map() {
    $rows = Utils::get_option('synonyms');
    $map  = array();

    if (!is_array($rows)) {
      return $map;
    }

    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }

      $word_raw     = isset($row['word']) ? $row['word'] : '';
      $synonyms_raw = isset($row['synonyms']) ? $row['synonyms'] : '';
      $word         = strtolower(trim(sanitize_text_field((string) $word_raw)));
      $synonyms     = array_filter(array_map('trim', explode(',', (string) $synonyms_raw)));
      $synonyms     = array_unique(array_map('sanitize_text_field', $synonyms));

      if ($word === '' || empty($synonyms)) {
        continue;
      }

      $map[$word] = implode(' ', $synonyms);
    }

    return $map;
  }

  /**
   * Title-only filter: every whitespace-separated query token must appear in the title (AND).
   * Per token, the literal token or any synonym word from settings counts as a match (OR).
   *
   * @param string $title
   * @param string $search_query
   * @return bool
   */
  private function title_contains_search_terms($title, $search_query) {
    $title        = strtolower(trim(sanitize_text_field((string) $title)));
    $search_query = strtolower(trim(sanitize_text_field((string) $search_query)));

    if ($title === '' || $search_query === '') {
      return false;
    }

    $synonym_map = $this->get_synonym_map();
    $tokens      = preg_split('/\s+/', $search_query);

    if (!is_array($tokens)) {
      return false;
    }

    $had_token = false;

    foreach ($tokens as $token) {
      $token = trim($token);

      if ($token === '') {
        continue;
      }

      $had_token = true;

      if (strpos($title, $token) !== false) {
        continue;
      }

      $matched_synonym = false;

      if (isset($synonym_map[$token])) {
        $syn_parts = preg_split('/\s+/', strtolower(trim($synonym_map[$token])));

        if (is_array($syn_parts)) {
          foreach ($syn_parts as $syn) {
            $syn = trim($syn);

            if ($syn !== '' && strpos($title, $syn) !== false) {
              $matched_synonym = true;
              break;
            }
          }
        }
      }

      if (!$matched_synonym) {
        return false;
      }
    }

    return $had_token;
  }

  /**
   * Get custom field values from a product and its variations
   *
   * @param int $product_id
   * @param string $meta_key
   * @param mixed $product
   * @return array
   */
  private function get_product_custom_field_values($product_id, $meta_key, $product) {
    $values = $this->normalize_custom_field_values(get_post_meta($product_id, $meta_key, true));

    if ($product && $product->is_type('variable')) {
      $variation_ids = $product->get_children();

      if (!empty($variation_ids)) {
        foreach ($variation_ids as $variation_id) {
          $variation_values = $this->normalize_custom_field_values(get_post_meta($variation_id, $meta_key, true));

          if (!empty($variation_values)) {
            $values = array_merge($values, $variation_values);
          }
        }
      }
    }

    return array_values(array_unique($values));
  }

  /**
   * Normalize custom field values to a sanitized array
   *
   * @param mixed $meta_value
   * @return array
   */
  private function normalize_custom_field_values($meta_value) {
    $values = array();

    if (is_array($meta_value)) {
      foreach ($meta_value as $value) {
        if (is_array($value)) {
          continue;
        }

        $sanitized_value = sanitize_text_field((string) $value);

        if ($sanitized_value !== '') {
          $values[] = $sanitized_value;
        }
      }
    } else {
      $sanitized_value = sanitize_text_field((string) $meta_value);

      if ($sanitized_value !== '') {
        $values[] = $sanitized_value;
      }
    }

    return $values;
  }

  /**
   * Get latest post type entries for preload rendering
   *
   * @param string $post_type
   * @param array $options
   * @return array
   */
  private function get_latest_content_items($post_type, $options) {
    $safe_limit   = 12;
    $posts        = get_posts(array(
      'post_type'      => $post_type,
      'post_status'    => 'publish',
      'orderby'        => 'date',
      'order'          => 'DESC',
      'posts_per_page' => $safe_limit,
    ));
    $posts_data    = array();

    foreach ($posts as $post) {
      $posts_data[] = array(
        'id'        => $post->ID,
        'title'     => get_the_title($post->ID),
        'thumbnail' => get_the_post_thumbnail_url($post->ID, 'medium'),
        'excerpt'   => $this->get_excerpt($post->post_content),
        'permalink' => get_permalink($post->ID),
      );
    }

    return $posts_data;
  }

  /**
   * Get latest enabled post type entries for preload/latest rendering
   *
   * @return array
   */
  private function get_latest_enabled_results() {
    $posts_options     = Utils::get_option('posts');
    $pages_options     = Utils::get_option('pages');
    $products_options  = Utils::get_option('products');
    $downloads_options = Utils::get_option('downloads');
    $posts_enabled     = isset($posts_options['enabled']) ? $posts_options['enabled'] : 1;
    $pages_enabled     = isset($pages_options['enabled']) ? $pages_options['enabled'] : 0;
    $products_enabled  = isset($products_options['enabled']) ? $products_options['enabled'] : 0;
    $downloads_enabled = isset($downloads_options['enabled']) ? $downloads_options['enabled'] : 0;
    $results           = array();

    if ($posts_enabled) {
      $results['posts'] = $this->get_latest_content_items('post', $posts_options);
    }

    if ($pages_enabled) {
      $results['pages'] = $this->get_latest_content_items('page', $pages_options);
    }

    if ($products_enabled) {
      $results['products'] = $this->get_latest_products($products_options);
    }

    if ($downloads_enabled) {
      $results['downloads'] = $this->get_latest_content_items('download', $downloads_options);
    }

    return $results;
  }

  /**
   * Get latest published products for preload/latest rendering
   *
   * @param array $options
   * @return array
   */
  private function get_latest_products($options) {
    if (!class_exists('WooCommerce')) {
      return array();
    }

    $safe_limit       = 12;
    $query_limit      = 48;
    $custom_fields_raw = Utils::get_option('filters_custom_fields');
    $custom_fields     = $custom_fields_raw ? array_filter(array_map('trim', explode(',', $custom_fields_raw))) : array();
    $posts      = get_posts(array(
      'post_type'      => 'product',
      'post_status'    => 'publish',
      'orderby'        => 'date',
      'order'          => 'DESC',
      'posts_per_page' => $query_limit,
    ));
    $posts_data = array();
    $tags = array(
      'div'  => array('class' => array(), 'role' => array(), 'aria-label' => array()),
      'span' => array('class' => array(), 'style' => array()),
    );

    foreach ($posts as $post) {
      $product = wc_get_product($post->ID);

      if (!$product || $product->get_catalog_visibility() === 'hidden' || !$product->is_in_stock()) {
        continue;
      }

      $average_rating = get_post_meta($post->ID, '_wc_average_rating', true) ?: 0;
      $is_featured    = $product->is_featured();
      $is_variable    = $product->is_type('variable');
      $add_to_cart_url = $product->add_to_cart_url();
      $product_custom_fields = array();

      if (!empty($custom_fields)) {
        foreach ($custom_fields as $custom_field) {
          $meta_key   = sanitize_key($custom_field);
          $meta_values = $this->get_product_custom_field_values($post->ID, $meta_key, $product);
          $product_custom_fields[$meta_key] = $meta_values;
        }
      }

      $posts_data[] = array(
        'id'              => $post->ID,
        'title'           => get_the_title($post->ID),
        'price'           => get_post_meta($post->ID, '_price', true),
        'thumbnail'       => get_the_post_thumbnail_url($post->ID, 'medium'),
        'excerpt'         => $this->get_excerpt($post->post_content),
        'average_rating'  => $average_rating ? (float) $average_rating : 0,
        'rating'          => $average_rating ? wp_kses(wc_get_rating_html((float) $average_rating), $tags) : wp_kses('<div class="star-rating"><span style="width:0%">No rating</span></div>', $tags),
        'is_featured'     => (bool) $is_featured,
        'is_variable'     => (bool) $is_variable,
        'is_in_stock'     => true,
        'add_to_cart_url' => esc_url_raw($add_to_cart_url),
        'permalink'       => get_permalink($post->ID),
        'custom_fields'   => $product_custom_fields,
      );

      if (count($posts_data) >= $safe_limit) {
        break;
      }
    }

    if (empty($posts_data)) {
      return $posts_data;
    }

    $featured_posts = array();
    $regular_posts  = array();

    foreach ($posts_data as $post_data) {
      if (!empty($post_data['is_featured'])) {
        $featured_posts[] = $post_data;
      } else {
        $regular_posts[] = $post_data;
      }
    }

    return array_merge($featured_posts, $regular_posts);
  }

  /**
   * Get top selling, in stock products for preload rendering
   *
   * @param array $options
   * @return array
   */
  private function get_top_selling_in_stock_products($options) {
    if (!class_exists('WooCommerce')) {
      return array();
    }

    $result_limit = isset($options['result_limit']) ? (int) $options['result_limit'] : 10;
    $safe_limit   = $result_limit > 0 ? $result_limit : 10;
    $posts        = get_posts(array(
      'post_type'      => 'product',
      'post_status'    => 'publish',
      'posts_per_page' => $safe_limit,
      'meta_key'       => 'total_sales',
      'orderby'        => 'meta_value_num',
      'order'          => 'DESC',
      'meta_query'     => array(
        array(
          'key'     => '_stock_status',
          'value'   => 'instock',
          'compare' => '=',
        ),
      ),
    ));
    $posts_data = array();
    $tags = array(
      'div'  => array('class' => array(), 'role' => array(), 'aria-label' => array()),
      'span' => array('class' => array(), 'style' => array()),
    );

    foreach ($posts as $post) {
      $product = wc_get_product($post->ID);

      if (!$product || $product->get_catalog_visibility() === 'hidden' || !$product->is_in_stock()) {
        continue;
      }

      $average_rating = get_post_meta($post->ID, '_wc_average_rating', true) ?: 0;
      $is_featured    = $product->is_featured();
      $is_variable    = $product->is_type('variable');
      $add_to_cart_url = $product->add_to_cart_url();

      $posts_data[] = array(
        'id'              => $post->ID,
        'title'           => get_the_title($post->ID),
        'price'           => get_post_meta($post->ID, '_price', true),
        'thumbnail'       => get_the_post_thumbnail_url($post->ID, 'medium'),
        'excerpt'         => $this->get_excerpt($post->post_content),
        'average_rating'  => $average_rating ? (float) $average_rating : 0,
        'rating'          => $average_rating ? wp_kses(wc_get_rating_html((float) $average_rating), $tags) : wp_kses('<div class="star-rating"><span style="width:0%">No rating</span></div>', $tags),
        'is_featured'     => (bool) $is_featured,
        'is_variable'     => (bool) $is_variable,
        'is_in_stock'     => true,
        'add_to_cart_url' => esc_url_raw($add_to_cart_url),
        'permalink'       => get_permalink($post->ID),
        'custom_fields'   => array(),
      );
    }

    return $posts_data;
  }

  /**
   * Get a trimmed excerpt
   *
   * @param string $content
   * @return string
   */
  private function get_excerpt($content) {
    $plain = wp_strip_all_tags((string) $content);
    $text  = function_exists('mb_substr') ? trim(mb_substr($plain, 0, 150)) : trim(substr($plain, 0, 150));

    if ($text === '') {
      return '';
    }

    return $text . '...';
  }

  /**
   * Is title only search enabled
   *
   * @return bool
   */
  private function is_title_only_search_enabled($type) {
    $options = Utils::get_option($type);

    if (!is_array($options) || !isset($options['title_only_search'])) {
      return false;
    }

    return !empty($options['title_only_search']);
  }

  /**
   * Whether boolean AND search is enabled for a settings group (posts, pages, products, downloads, orders).
   *
   * @param string $type Settings key.
   * @return bool
   */
  private function is_boolean_search_enabled($type) {
    $options = Utils::get_option($type);

    if (!is_array($options) || !isset($options['boolean_search'])) {
      return false;
    }

    return !empty($options['boolean_search']);
  }

  /**
   * Run TNTSearch relevance search or boolean AND when enabled (multi-word user query).
   *
   * @param object $tnt               TeamTNT TNTSearch instance.
   * @param string $expanded_query    Query after synonym expansion (single-token path).
   * @param int    $result_limit      Max results.
   * @param bool   $boolean_search    Settings flag.
   * @param string $user_search_query Raw user query for token boundaries (AND operands).
   * @return array                    Same shape as $tnt->search().
   */
  private function perform_tnt_search($tnt, $expanded_query, $result_limit, $boolean_search, $user_search_query) {
    if (!$boolean_search) {
      return $tnt->search($expanded_query, $result_limit);
    }

    $user_search_query = trim((string) $user_search_query);

    if ($user_search_query === '') {
      return $tnt->search($expanded_query, $result_limit);
    }

    $tokens = array_values(array_filter($tnt->breakIntoTokens($user_search_query)));

    if (count($tokens) < 2) {
      return $tnt->search($expanded_query, $result_limit);
    }

    $safe = array();

    foreach ($tokens as $token) {
      $token = (string) $token;

      if ($token === '') {
        continue;
      }

      if (preg_match('/[&|~()]/', $token)) {
        continue;
      }

      $safe[] = $token;
    }

    if (count($safe) < 2) {
      return $tnt->search($expanded_query, $result_limit);
    }

    // Expression::lex() turns every space into '&', so "a & b" becomes invalid extra operators — join with '&' only.
    $phrase  = implode('&', $safe);
    $results = $tnt->searchBoolean($phrase, $result_limit);

    if (isset($results['ids']) && is_array($results['ids'])) {
      $results['ids'] = array_values(array_filter(array_map('absint', $results['ids'])));
    }

    return $results;
  }

}