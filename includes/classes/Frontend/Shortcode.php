<?php

namespace PolyPlugins\Speedy_Search\Frontend;

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
    add_shortcode('snappy_search_polyplugins', array($this, 'snappy_search_shortcode_render'));
  }

  public function snappy_search_shortcode_render($atts) {
    $atts = shortcode_atts(array(
      'placeholder' => 'Search...',
    ), $atts, 'snappy_search');

    ob_start();
    ?>
    <div class="speedy-search-container" style="background-color: #ccc;">
      <form role="search" method="get" class="snappy-search-form" action="<?php echo esc_url(home_url('/')); ?>">
        <input type="text" class="snappy-search-input" placeholder="<?php echo esc_attr($atts['placeholder']); ?>" autocomplete="off" name="s">
        <button type="button" class="snappy-search-close" aria-label="Close Search">×</button>
      </form>
    </div>
    <?php
    return ob_get_clean();
  }
  
}