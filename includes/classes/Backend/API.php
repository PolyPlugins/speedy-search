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
    $search_key       = strtolower(trim($search_query));
    $cache_key        = 'speedy_search_combined_' . md5($search_key);
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
    $cache_key = 'speedy_search_posts_' . md5($search_query);

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
    $cache_key = 'speedy_search_pages_' . md5($search_query);

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

    // Generate a unique cache key for this search
    $cache_key = 'speedy_search_products_' . md5($search_query . '|' . (int) $out_of_stock_last);

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
        $product = wc_get_product($post->ID);

        if ($product && $product->get_catalog_visibility() === 'hidden') {
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
            $meta_value = get_post_meta($post->ID, $meta_key, true);

            if (is_array($meta_value)) {
              $meta_value = implode(', ', array_map('sanitize_text_field', $meta_value));
            } else {
              $meta_value = sanitize_text_field($meta_value);
            }

            $product_custom_fields[$meta_key] = $meta_value;
          }
        }
        
        $data = array(
          'id'             => $post->ID,
          'title'          => get_the_title($post->ID),
          'price'          => get_post_meta($post->ID, '_price', true),
          'thumbnail'      => get_the_post_thumbnail_url($post->ID, 'medium'),
          'excerpt'        => rtrim(substr(wp_strip_all_tags($post->post_content), 0, 150)) . '...',
          'average_rating' => $average_rating ? (float) $average_rating : 0,
          'rating'         => $average_rating ? wp_kses(wc_get_rating_html((float) $average_rating), $tags) : wp_kses('<div class="star-rating"><span style="width:0%">No rating</span></div>', $tags),
          'is_featured'    => (bool) $is_featured,
          'is_variable'    => (bool) $is_variable,
          'is_in_stock'    => (bool) $is_in_stock,
          'add_to_cart_url' => esc_url_raw($add_to_cart_url),
          'permalink'      => get_permalink($post->ID),
          'custom_fields'  => $product_custom_fields,
        );
        $posts_data[] = $data;
      }

      usort($posts_data, function($a, $b) use ($out_of_stock_last) {
        if ($out_of_stock_last && $a['is_in_stock'] !== $b['is_in_stock']) {
          return $a['is_in_stock'] ? -1 : 1;
        }

        if ($a['is_featured'] !== $b['is_featured']) {
          return $a['is_featured'] ? -1 : 1;
        }

        return $b['average_rating'] <=> $a['average_rating'];
      });

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
    $cache_key = 'speedy_search_downloads_' . md5($search_query);

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
    $cache_key = 'speedy_search_orders_' . md5($search_query);

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
    $results    = $tnt->search($search_query, $result_limit);
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

}