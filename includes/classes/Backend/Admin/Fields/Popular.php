<?php

namespace PolyPlugins\Speedy_Search\Backend\Admin\Fields;

use PolyPlugins\Speedy_Search\Backend\DB;
use PolyPlugins\Speedy_Search\Log;
use PolyPlugins\Speedy_Search\Utils;

if (!defined('ABSPATH')) exit;

class Popular {

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
    add_action('admin_post_speedy_search_popular_term', array($this, 'handle_popular_term'));
    add_action('wp_ajax_speedy_search_order_terms', array($this, 'order_terms'));
    add_action('admin_footer', array($this, 'render_terms_panel'));
  }
  
  /**
   * Add section
   *
   * @return void
   */
  public function add_section() {
    add_settings_section(
      'speedy_search_popular_section_polyplugins',
      '',
      null,
      'speedy_search_popular_polyplugins'
    );
  }
  
  /**
   * Add settings
   *
   * @return void
   */
  public function add_settings() {
		add_settings_field(
			'popular_enabled',
			__('Enabled?', 'speedy-search'),
			array($this, 'popular_enabled_render'),
			'speedy_search_popular_polyplugins',
			'speedy_search_popular_section_polyplugins'
		);

		add_settings_field(
			'popular_position',
			__('Display', 'speedy-search'),
			array($this, 'popular_position_render'),
			'speedy_search_popular_polyplugins',
			'speedy_search_popular_section_polyplugins'
		);

		add_settings_field(
			'popular_heading',
			__('Heading', 'speedy-search'),
			array($this, 'popular_heading_render'),
			'speedy_search_popular_polyplugins',
			'speedy_search_popular_section_polyplugins'
		);

		add_settings_field(
			'popular_term_source',
			__('Show terms', 'speedy-search'),
			array($this, 'popular_term_source_render'),
			'speedy_search_popular_polyplugins',
			'speedy_search_popular_section_polyplugins'
		);

		add_settings_field(
			'popular_limit',
			__('Limit', 'speedy-search'),
			array($this, 'popular_limit_render'),
			'speedy_search_popular_polyplugins',
			'speedy_search_popular_section_polyplugins'
		);
    
		add_settings_field(
			'popular_days',
			__('Days', 'speedy-search'),
			array($this, 'popular_days_render'),
			'speedy_search_popular_polyplugins',
			'speedy_search_popular_section_polyplugins'
		);
    
		add_settings_field(
			'popular_tracking_delay',
			__('Tracking Delay', 'speedy-search'),
			array($this, 'popular_tracking_delay_render'),
			'speedy_search_popular_polyplugins',
			'speedy_search_popular_section_polyplugins'
		);
    
		add_settings_field(
			'popular_characters',
			__('Characters', 'speedy-search'),
			array($this, 'popular_characters_render'),
			'speedy_search_popular_polyplugins',
			'speedy_search_popular_section_polyplugins'
		);
    
		add_settings_field(
			'popular_result_count',
			__('Result Count', 'speedy-search'),
			array($this, 'popular_result_count_render'),
			'speedy_search_popular_polyplugins',
			'speedy_search_popular_section_polyplugins'
		);
  }
  
  /**
	 * Render Enabled Field
	 *
	 * @return void
	 */
	public function popular_enabled_render() {
		$options = Utils::get_option('popular');
    $option  = isset($options['enabled']) ? $options['enabled'] : 0;
    ?>
    <div class="form-check form-switch">
      <input type="checkbox" name="speedy_search_settings_polyplugins[popular][enabled]" class="form-check-input" role="switch" <?php checked(1, $option, true); ?> /> <?php esc_html_e('Yes', 'speedy-search'); ?>
    </div>
    <p><strong><?php esc_html_e('Enabling this will track searches and display popular search terms. This will track what users are searching, but it is not tied to individual users. Before you enable, please make sure you are in compliance with data protection regulations.', 'speedy-search'); ?></strong></p>
		<?php
	}

  /**
   * Render the popular term placement field.
   *
   * @return void
   */
  public function popular_position_render() {
    $options  = Utils::get_option('popular');
    $option   = isset($options['position']) ? $options['position'] : 'under_input';
    $choices  = array(
      'under_input'   => __('Under the search field', 'speedy-search'),
      'above_tabs'    => __('Above the result tabs', 'speedy-search'),
      'below_tabs'    => __('Below the result tabs', 'speedy-search'),
      'below_results' => __('Below the results', 'speedy-search'),
    );

    if (!isset($choices[$option])) {
      $option = 'under_input';
    }
    ?>
    <select name="speedy_search_settings_polyplugins[popular][position]">
      <?php foreach ($choices as $value => $label) : ?>
        <option value="<?php echo esc_attr($value); ?>" <?php selected($option, $value); ?>><?php echo esc_html($label); ?></option>
      <?php endforeach; ?>
    </select>
    <p><strong><?php esc_html_e('Where the popular search terms are shown. Spots inside the results panel only appear while that panel is open.', 'speedy-search'); ?></strong></p>
    <?php
  }

  /**
   * Render the text shown above popular terms.
   *
   * @return void
   */
  public function popular_heading_render() {
    $options = Utils::get_option('popular');
    $option  = isset($options['heading']) ? $options['heading'] : __('Popular Searches', 'speedy-search');
    ?>
    <input type="text" name="speedy_search_settings_polyplugins[popular][heading]" value="<?php echo esc_attr($option); ?>" />
    <p><strong><?php esc_html_e('Text shown above the popular search terms. Clear this field to hide it.', 'speedy-search'); ?></strong></p>
    <?php
  }

  /**
   * Render which popular terms are shown.
   *
   * @return void
   */
  public function popular_term_source_render() {
    $options = Utils::get_option('popular');
    $option  = isset($options['term_source']) ? $options['term_source'] : 'manual';
    $choices = array(
      'manual'    => __('Manual', 'speedy-search'),
      'automatic' => __('Automatic', 'speedy-search'),
      'both'      => __('Manual and automatic', 'speedy-search'),
    );

    if (!isset($choices[$option])) {
      $option = 'manual';
    }
    ?>
    <select name="speedy_search_settings_polyplugins[popular][term_source]">
      <?php foreach ($choices as $value => $label) : ?>
        <option value="<?php echo esc_attr($value); ?>" <?php selected($option, $value); ?>><?php echo esc_html($label); ?></option>
      <?php endforeach; ?>
    </select>
    <p><strong><?php esc_html_e('Manual shows terms you add and terms you approve. Automatic shows approved searches from recent history. Manual and automatic shows both, with terms you add first.', 'speedy-search'); ?></strong></p>
    <?php
  }

  /**
   * Print the Terms panel for the Popular item in the settings menu.
   *
   * The add form stays outside the settings form. The text field uses the form attribute.
   *
   * @return void
   */
  public function render_terms_panel() {
    if (!current_user_can('manage_options')) {
      return;
    }

    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';

    if ($page !== 'speedy-search') {
      return;
    }
    ?>
    <form id="snappy-terms-add" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <?php wp_nonce_field('speedy_search_popular_term'); ?>
      <input type="hidden" name="action" value="speedy_search_popular_term" />
      <input type="hidden" name="do" value="add" />
    </form>
    <form id="snappy-terms-blacklist" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <?php wp_nonce_field('speedy_search_popular_term'); ?>
      <input type="hidden" name="action" value="speedy_search_popular_term" />
      <input type="hidden" name="do" value="blacklist" />
    </form>
    <div class="tab terms" id="snappy-terms-panel">
      <div class="snappy-terms">
        <p><?php esc_html_e('Add a pending term to show it. Drag added terms to set the order. Terms below the line are not shown. Blacklisted terms stay hidden.', 'speedy-search'); ?></p>
        <?php
        $notice = '';

        if (isset($_GET['snappy_terms_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['snappy_terms_nonce'])), 'speedy_search_terms_notice')) {
          $notice = isset($_GET['snappy_terms']) ? sanitize_key(wp_unslash($_GET['snappy_terms'])) : '';
        }

        if ($notice === 'full') {
          echo '<p class="snappy-terms-note">' . esc_html__('The added list is full. Remove a term before adding another.', 'speedy-search') . '</p>';
        }

        $limit         = DB::popular_limit();
        $cap           = DB::added_term_cap();
        $pending_total = DB::count_terms_by_status('pending');
        $pending_note  = '';

        if ($pending_total > $cap) {
          $pending_note = sprintf(
            /* translators: 1: number shown, 2: total pending terms */
            __('Showing %1$d of %2$d pending terms.', 'speedy-search'),
            $cap,
            $pending_total
          );
        }
        ?>
        <div class="snappy-terms-add">
          <input type="text" name="term" form="snappy-terms-add" maxlength="255" placeholder="<?php esc_attr_e('Add a term', 'speedy-search'); ?>" />
          <button type="submit" class="button button-primary" form="snappy-terms-add"><?php esc_html_e('Add Term', 'speedy-search'); ?></button>
        </div>
        <?php
        $this->render_term_list(__('Added', 'speedy-search'), DB::get_terms_by_status('approved', '', $cap), 'added', $limit);
        $this->render_term_list(__('Pending', 'speedy-search'), DB::get_terms_by_status('pending', '', $cap), 'pending', 0, $pending_note);
        $this->render_term_list(__('Blacklist', 'speedy-search'), DB::get_terms_by_status('rejected'), 'blacklist');
        ?>
      </div>
    </div>
    <?php
  }

  /**
   * Approve, add, or remove a popular term.
   *
   * @return void
   */
  public function handle_popular_term() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have permission to manage popular terms.', 'speedy-search'));
    }

    check_admin_referer('speedy_search_popular_term');

    $do   = isset($_REQUEST['do']) ? sanitize_key(wp_unslash($_REQUEST['do'])) : '';
    $term = isset($_REQUEST['term']) ? sanitize_text_field(wp_unslash($_REQUEST['term'])) : '';
    $full = false;

    if ($do === 'add' && $term !== '') {
      $full = !DB::set_term_status($term, 'approved', 'manual');
    } elseif ($do === 'approve' && $term !== '') {
      $existing = DB::get_term_status($term);
      $source   = (is_array($existing) && !empty($existing['source'])) ? $existing['source'] : 'tracked';

      $full = !DB::set_term_status($term, 'approved', $source);
    } elseif ($do === 'blacklist' && $term !== '') {
      $existing = DB::get_term_status($term);
      $source   = (is_array($existing) && !empty($existing['source'])) ? $existing['source'] : 'tracked';

      DB::set_term_status($term, 'rejected', $source);
    } elseif ($do === 'remove' && $term !== '') {
      $existing = DB::get_term_status($term);

      if (is_array($existing) && isset($existing['status']) && in_array($existing['status'], array('approved', 'rejected'), true)) {
        DB::delete_term_status($term);
      }
    }

    $redirect = admin_url('admin.php?page=speedy-search&tab=terms');

    if ($full) {
      $redirect = add_query_arg(
        array(
          'snappy_terms'       => 'full',
          'snappy_terms_nonce' => wp_create_nonce('speedy_search_terms_notice'),
        ),
        $redirect
      );
    }

    wp_safe_redirect($redirect);
    exit;
  }

  /**
   * Save the dragged order of added terms.
   *
   * @return void
   */
  public function order_terms() {
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }

    check_ajax_referer('speedy_search_order_terms', 'nonce');

    $terms = isset($_POST['terms']) ? map_deep(wp_unslash($_POST['terms']), 'sanitize_text_field') : array();

    if (!is_array($terms)) {
      $terms = array();
    }

    DB::set_term_positions($terms);
    wp_send_json_success();
  }

  /**
   * Print one status list.
   *
   * @param string $label   Section heading.
   * @param array  $rows    Term rows.
   * @param string $actions    added, pending, or blacklist.
   * @param int    $show_limit Popular limit. A line is drawn after this many added terms.
   * @param string $note       Optional note under the heading.
   * @return void
   */
  private function render_term_list($label, $rows, $actions, $show_limit = 0, $note = '') {
    $sources = array(
      'tracked' => __('Tracked', 'speedy-search'),
      'manual'  => __('Manual', 'speedy-search'),
      'auto'    => __('Auto', 'speedy-search'),
    );
    $empty = array(
      'added'     => __('No added terms.', 'speedy-search'),
      'pending'   => __('No pending terms.', 'speedy-search'),
      'blacklist' => __('No blacklisted terms.', 'speedy-search'),
    );
    $sortable = ($actions === 'added');
    ?>
    <div class="snappy-terms-section">
    <h2><?php echo esc_html($label); ?></h2>
    <?php if ($note !== '') : ?>
      <p class="snappy-terms-note"><?php echo esc_html($note); ?></p>
    <?php endif; ?>
    <?php if ($actions === 'blacklist') : ?>
      <div class="snappy-terms-add">
        <input type="text" name="term" form="snappy-terms-blacklist" maxlength="255" placeholder="<?php esc_attr_e('Add a term', 'speedy-search'); ?>" />
        <button type="submit" class="button button-primary" form="snappy-terms-blacklist"><?php esc_html_e('Add Term', 'speedy-search'); ?></button>
      </div>
    <?php endif; ?>
    <?php if (!$rows) : ?>
      <p><?php echo esc_html(isset($empty[$actions]) ? $empty[$actions] : ''); ?></p>
      </div>
      <?php return; ?>
    <?php endif; ?>
    <table class="widefat striped<?php echo $sortable ? ' snappy-terms-sortable' : ''; ?>"<?php echo $sortable ? ' data-show-limit="' . esc_attr($show_limit) . '" data-nonce="' . esc_attr(wp_create_nonce('speedy_search_order_terms')) . '"' : ''; ?>>
      <thead>
        <tr>
          <?php if ($sortable) : ?>
            <th class="snappy-term-handle-col"></th>
          <?php endif; ?>
          <th><?php esc_html_e('Term', 'speedy-search'); ?></th>
          <?php if ($actions === 'pending') : ?>
            <th><?php esc_html_e('Searches', 'speedy-search'); ?></th>
          <?php endif; ?>
          <th><?php esc_html_e('Source', 'speedy-search'); ?></th>
          <th><?php esc_html_e('Actions', 'speedy-search'); ?></th>
        </tr>
      </thead>
      <tbody class="snappy-terms-sortable-body">
        <?php $index = 0; ?>
        <?php foreach ($rows as $row) : ?>
          <?php
          $index++;
          $term   = isset($row['term']) ? $row['term'] : '';
          $source = isset($row['source']) ? $row['source'] : '';
          ?>
          <tr class="<?php echo $sortable ? 'snappy-term-row' : ''; ?>"<?php echo $sortable ? ' data-term="' . esc_attr($term) . '"' : ''; ?>>
            <?php if ($sortable) : ?>
              <td class="snappy-term-handle" title="<?php esc_attr_e('Drag to reorder', 'speedy-search'); ?>"><span class="dashicons dashicons-menu"></span></td>
            <?php endif; ?>
            <td><?php echo esc_html($term); ?></td>
            <?php if ($actions === 'pending') : ?>
              <td><?php echo esc_html(isset($row['searches']) ? (int) $row['searches'] : 0); ?></td>
            <?php endif; ?>
            <td><?php echo esc_html(isset($sources[$source]) ? $sources[$source] : $source); ?></td>
            <td>
              <?php if ($actions === 'added') : ?>
                <a href="<?php echo esc_url($this->popular_term_action_url('remove', $term)); ?>"><?php esc_html_e('Remove', 'speedy-search'); ?></a>
                |
                <a href="<?php echo esc_url($this->popular_term_action_url('blacklist', $term)); ?>"><?php esc_html_e('Blacklist', 'speedy-search'); ?></a>
              <?php endif; ?>
              <?php if ($actions === 'pending') : ?>
                <a href="<?php echo esc_url($this->popular_term_action_url('approve', $term)); ?>"><?php esc_html_e('Add', 'speedy-search'); ?></a>
                |
                <a href="<?php echo esc_url($this->popular_term_action_url('blacklist', $term)); ?>"><?php esc_html_e('Blacklist', 'speedy-search'); ?></a>
              <?php endif; ?>
              <?php if ($actions === 'blacklist') : ?>
                <a href="<?php echo esc_url($this->popular_term_action_url('remove', $term)); ?>"><?php esc_html_e('Remove', 'speedy-search'); ?></a>
              <?php endif; ?>
            </td>
          </tr>
          <?php if ($sortable && $show_limit > 0 && $index === $show_limit && count($rows) > $show_limit) : ?>
            <tr class="snappy-term-limit">
              <td colspan="4"><?php esc_html_e('Terms below this line are not shown.', 'speedy-search'); ?></td>
            </tr>
          <?php endif; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php
  }

  /**
   * Build an admin-post link that does not submit the settings form.
   *
   * @param string $do   approve, blacklist, or remove.
   * @param string $term Search term.
   * @return string
   */
  private function popular_term_action_url($do, $term) {
    return wp_nonce_url(
      add_query_arg(
        array(
          'action' => 'speedy_search_popular_term',
          'do'     => $do,
          'term'   => $term,
        ),
        admin_url('admin-post.php')
      ),
      'speedy_search_popular_term'
    );
  }

  /**
	 * Render Limit
	 *
	 * @return void
	 */
	public function popular_limit_render() {
		$options = Utils::get_option('popular');
    $option  = isset($options['limit']) && is_numeric($options['limit']) ? $options['limit'] : 5;
    ?>
    <input type="number" name="speedy_search_settings_polyplugins[popular][limit]" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('Number of popular search terms to show.', 'speedy-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Popular Days
	 *
	 * @return void
	 */
	public function popular_days_render() {
		$options = Utils::get_option('popular');
    $option  = isset($options['days']) && is_numeric($options['days']) ? $options['days'] : 30;
    ?>
    <input type="number" name="speedy_search_settings_polyplugins[popular][days]" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('The number of days of search term history to look through for popular search terms. Note: We run a background worker that will cleanup any search term history beyond the number of days you set.', 'speedy-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Popular Tracking Delay
	 *
	 * @return void
	 */
	public function popular_tracking_delay_render() {
		$options = Utils::get_option('popular');
    $option  = isset($options['delay']) && is_numeric($options['delay']) ? $options['delay'] : 3000;
    ?>
    <input type="number" name="speedy_search_settings_polyplugins[popular][delay]" id="tracking_delay" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('How many milliseconds after a user is done typing, until the search is tracked?', 'speedy-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Characters
	 *
	 * @return void
	 */
	public function popular_characters_render() {
		$options = Utils::get_option('popular');
    $option  = isset($options['characters']) && is_numeric($options['characters']) ? $options['characters'] : 3;
    ?>
    <input type="number" name="speedy_search_settings_polyplugins[popular][characters]" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('Number of characters required for the search term to be tracked.', 'speedy-search'); ?></strong></p>
	  <?php
	}

  /**
	 * Render Popular Result Count
	 *
	 * @return void
	 */
	public function popular_result_count_render() {
		$options = Utils::get_option('popular');
    $option  = isset($options['result_count']) && is_numeric($options['result_count']) ? $options['result_count'] : 10;
    ?>
    <input type="number" name="speedy_search_settings_polyplugins[popular][result_count]" value="<?php echo esc_html($option); ?>">
    <p><strong><?php esc_html_e('The number of found results for a search term to be considered popular. Set higher than 0 to prevent search terms with no results from showing as a popular term.', 'speedy-search'); ?></strong></p>
	  <?php
	}

}