<?php

namespace PolyPlugins\Speedy_Search\Backend\Admin\Fields;

use PolyPlugins\Speedy_Search\Log;
use PolyPlugins\Speedy_Search\Utils;

if (!defined('ABSPATH')) exit;

class Exclusions {

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
    Log::debug(sprintf('Snappy Search settings fields: %s', __CLASS__));

		$this->add_section();
    $this->add_settings();
  }
  
  /**
   * Add section
   *
   * @return void
   */
  public function add_section() {
    add_settings_section(
      'speedy_search_exclusions_section_polyplugins',
      '',
      null,
      'speedy_search_exclusions_polyplugins'
    );
  }
  
  /**
   * Add settings
   *
   * @return void
   */
  public function add_settings() {
		add_settings_field(
			'exclusions',
			__('Exclusions', 'speedy-search'),
			array($this, 'exclusions_render'),
			'speedy_search_exclusions_polyplugins',
			'speedy_search_exclusions_section_polyplugins'
		);
  }

  /**
	 * Render Exclusions Field
	 *
	 * @return void
	 */
	public function exclusions_render() {
    $rows = Utils::get_option('exclusions');

    if (!is_array($rows) || empty($rows)) {
      $rows = array(
        array(
          'when'    => '',
          'exclude' => '',
        ),
      );
    }
    ?>
    <div class="snappy-exclusions-wrapper" data-next-index="<?php echo esc_attr(count($rows)); ?>">
      <div class="snappy-exclusions-rows">
        <?php foreach ($rows as $index => $row) : ?>
          <?php
          $when    = isset($row['when']) ? sanitize_text_field($row['when']) : '';
          $exclude = isset($row['exclude']) ? sanitize_textarea_field($row['exclude']) : '';
          ?>
          <div class="snappy-exclusion-row">
            <p class="snappy-exclusion-label"><strong><?php esc_html_e('When search contains', 'speedy-search'); ?></strong></p>
            <input type="text" name="speedy_search_settings_polyplugins[exclusions][<?php echo esc_attr($index); ?>][when]" value="<?php echo esc_attr($when); ?>" />
            <p class="snappy-exclusion-label"><strong><?php esc_html_e('Exclude results containing (comma separated)', 'speedy-search'); ?></strong></p>
            <textarea rows="3" name="speedy_search_settings_polyplugins[exclusions][<?php echo esc_attr($index); ?>][exclude]"><?php echo esc_textarea($exclude); ?></textarea>
            <button type="button" class="button button-secondary snappy-exclusion-remove"><?php esc_html_e('Remove', 'speedy-search'); ?></button>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="button button-primary snappy-exclusion-add"><?php esc_html_e('Add More', 'speedy-search'); ?></button>
      <p><strong><?php esc_html_e('Example: When search contains "running shoes", exclude "cleat" to hide soccer cleats or similar items.', 'speedy-search'); ?></strong></p>
    </div>
		<?php
	}

}
