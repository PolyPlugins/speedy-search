<?php

namespace PolyPlugins\Speedy_Search\Backend\Admin\Fields;

use PolyPlugins\Speedy_Search\Utils;

if (!defined('ABSPATH')) exit;

class Synonyms {

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
      'speedy_search_synonyms_section_polyplugins',
      '',
      null,
      'speedy_search_synonyms_polyplugins'
    );
  }
  
  /**
   * Add settings
   *
   * @return void
   */
  public function add_settings() {
		add_settings_field(
			'synonyms',
			__('Synonyms', 'speedy-search'),
			array($this, 'synonyms_render'),
			'speedy_search_synonyms_polyplugins',
			'speedy_search_synonyms_section_polyplugins'
		);
  }

  /**
	 * Render Synonyms Field
	 *
	 * @return void
	 */
	public function synonyms_render() {
    $rows = Utils::get_option('synonyms');

    if (!is_array($rows) || empty($rows)) {
      $rows = array(
        array(
          'word' => '',
          'synonyms' => '',
        ),
      );
    }
    ?>
    <div class="snappy-synonyms-wrapper" data-next-index="<?php echo esc_attr(count($rows)); ?>">
      <div class="snappy-synonyms-rows">
        <?php foreach ($rows as $index => $row) : ?>
          <?php
          $word     = isset($row['word']) ? sanitize_text_field($row['word']) : '';
          $synonyms = isset($row['synonyms']) ? sanitize_textarea_field($row['synonyms']) : '';
          ?>
          <div class="snappy-synonym-row">
            <p class="snappy-synonym-label"><strong><?php esc_html_e('Word', 'speedy-search'); ?></strong></p>
            <input type="text" name="speedy_search_settings_polyplugins[synonyms][<?php echo esc_attr($index); ?>][word]" value="<?php echo esc_attr($word); ?>" />
            <p class="snappy-synonym-label"><strong><?php esc_html_e('Synonyms (comma separated)', 'speedy-search'); ?></strong></p>
            <textarea rows="3" name="speedy_search_settings_polyplugins[synonyms][<?php echo esc_attr($index); ?>][synonyms]"><?php echo esc_textarea($synonyms); ?></textarea>
            <button type="button" class="button button-secondary snappy-synonym-remove"><?php esc_html_e('Remove', 'speedy-search'); ?></button>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="button button-primary snappy-synonym-add"><?php esc_html_e('Add More', 'speedy-search'); ?></button>
      <p><strong><?php esc_html_e('Add one word and a comma separated list of synonyms. Example: sofa => couch, loveseat, sectional', 'speedy-search'); ?></strong></p>
    </div>
		<?php
	}

}
