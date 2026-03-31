<?php
use PolyPlugins\Speedy_Search\Backend\DB;
use PolyPlugins\Speedy_Search\Utils;

if (!defined('ABSPATH')) exit;

$is_indexing_speedy_search          = Utils::is_indexing();
$popular_options_speedy_search      = Utils::get_option('popular');
$popular_enabled_speedy_search      = isset($popular_options_speedy_search['enabled']) ? $popular_options_speedy_search['enabled'] : 0;
$characters_speedy_search           = Utils::get_option('characters');
$advanced_options_speedy_search     = Utils::get_option('advanced');
$advanced_placeholder_speedy_search = isset($advanced_options_speedy_search['placeholder']) ? $advanced_options_speedy_search['placeholder'] : __('Search...', 'speedy-search');
$advanced_enabled_speedy_search     = isset($advanced_options_speedy_search['enabled']) ? $advanced_options_speedy_search['enabled'] : 0;
$advanced_page_slug_speedy_search   = Utils::get_page_slug_by_template();
$action_speedy_search               = $advanced_enabled_speedy_search && $advanced_page_slug_speedy_search && !$is_indexing_speedy_search ? home_url('/' . $advanced_page_slug_speedy_search . '/') : home_url('/');
$name_speedy_search                 = $advanced_enabled_speedy_search && $advanced_page_slug_speedy_search && !$is_indexing_speedy_search ? 'search' : 's';
$search_speedy_search               = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

get_header();
?>

<div class="speedy-search-container advanced-search stacked">
  <form role="search" method="get" class="snappy-search-form" action="<?php echo esc_url($action_speedy_search); ?>">
    <input type="text" class="snappy-search-advanced-input" placeholder="<?php echo esc_attr($advanced_placeholder_speedy_search); ?>" autocomplete="off" name="<?php echo esc_html($name_speedy_search); ?>" value="<?php echo esc_html($search_speedy_search); ?>">
    <button type="button" class="snappy-search-close" aria-label="Close Search">×</button>
    <span class="loader" style="display: none;"></span>
  </form>

  <?php if (strlen(trim($search_speedy_search)) < $characters_speedy_search && strlen(trim($search_speedy_search)) > 0)  : ?>
    <p class="search-error"><?php esc_html_e('Your search could not be completed because it needs to be at least', 'speedy-search'); ?> <?php echo esc_html($characters_speedy_search); ?> <?php esc_attr_e('characters.', 'speedy-search'); ?></p>
  <?php endif; ?>
  
  <?php if (!$is_indexing_speedy_search) : ?>
    <?php if ($popular_enabled_speedy_search) : ?>
      <div class="popular-searches">
        <p class="popular"><?php esc_attr_e('Popular Searches', 'speedy-search'); ?></p>
        <?php
        $popular_speedy_search = DB::get_top_terms_last_x_days();
        ?>
        <?php foreach ($popular_speedy_search as $term) : ?>
          <a href="javascript:void(0);" class="search-term"><?php echo esc_html($term['term']); ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php get_footer(); ?>