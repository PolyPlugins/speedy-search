<?php

namespace PolyPlugins\Speedy_Search\Frontend;

use PolyPlugins\Speedy_Search\Backend\DB;
use PolyPlugins\Speedy_Search\Utils;

class Shortcode {

  /**
	 * Full path and filename of plugin.
	 *
	 * @var string $version Full path and filename of plugin.
	 */
  private $plugin;

	/**
	 * The version of this plugin.
	 *
	 * @var   string $version The current version of this plugin.
	 */
	private $version;

  /**
   * The URL to the plugin directory.
   *
   * @var string $plugin_dir_url URL to the plugin directory.
   */
	private $plugin_dir_url;
  
  /**
   * __construct
   *
   * @return void
   */
  public function __construct($plugin, $version, $plugin_dir_url) {
    $this->plugin         = $plugin;
    $this->version        = $version;
    $this->plugin_dir_url = $plugin_dir_url;
  }
  
  /**
   * Init
   *
   * @return void
   */
  public function init() {
    add_shortcode('speedy_search_polyplugins', array($this, 'snappy_search_shortcode_render'));
    add_shortcode('snappy_search_polyplugins', array($this, 'snappy_search_shortcode_render'));
  }

  public function snappy_search_shortcode_render($atts) {
    $atts = shortcode_atts(array(
      'placeholder' => 'Search...',
    ), $atts, 'snappy_search');

    ob_start();

    
		$popular_options = Utils::get_option('popular');
    $enabled         = isset($popular_options['enabled']) ? $popular_options['enabled'] : 0;
    ?>
    <div class="speedy-search-container">
      <form role="search" method="get" class="snappy-search-form" action="<?php echo esc_url(home_url('/')); ?>">
        <input type="text" class="snappy-search-input" placeholder="<?php echo esc_attr($atts['placeholder']); ?>" autocomplete="off" name="s">
        <button type="button" class="snappy-search-close" aria-label="Close Search">×</button>
      </form>
      <?php if ($enabled) : ?>
        <div class="popular-searches">
          <p class="popular">Popular Searches</p>
          <?php
          $popular = DB::get_top_terms_last_x_days();
          ?>
          <?php foreach ($popular as $term) : ?>
            <a href="javascript:void(0);" class="search-term"><?php echo esc_html($term['term']); ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
  }
  
}