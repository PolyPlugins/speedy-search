<?php
use PolyPlugins\Speedy_Search\Backend\DB;
use PolyPlugins\Speedy_Search\Utils;

if (!defined('ABSPATH')) exit;

$is_indexing_speedy_search        = Utils::is_indexing();
$popular_options_speedy_search    = Utils::get_option('popular');
$popular_enabled_speedy_search    = isset($popular_options_speedy_search['enabled']) ? $popular_options_speedy_search['enabled'] : 0;
$advanced_options_speedy_search   = Utils::get_option('advanced');
$advanced_enabled_speedy_search   = isset($advanced_options_speedy_search['enabled']) ? $advanced_options_speedy_search['enabled'] : 0;
$advanced_page_slug_speedy_search = Utils::get_page_slug_by_template();
$action_speedy_search             = $advanced_enabled_speedy_search && $advanced_page_slug_speedy_search && !$is_indexing_speedy_search ? home_url('/' . $advanced_page_slug_speedy_search . '/') : home_url('/');
$name_speedy_search               = $advanced_enabled_speedy_search && $advanced_page_slug_speedy_search && !$is_indexing_speedy_search ? 'search' : 's';
?>

<div class="speedy-search-container mobile">
  <form role="search" method="get" class="snappy-search-form" action="<?php echo esc_url($action_speedy_search); ?>">
    <input type="text" class="snappy-search-input" placeholder="<?php echo esc_attr($atts['placeholder']); ?>" autocomplete="off" name="<?php echo esc_html($name_speedy_search); ?>">
    <button type="button" class="snappy-search-close" aria-label="Close Search">×</button>
    <span class="loader" style="display: none;"></span>
  </form>
  
  <?php if (!$is_indexing_speedy_search) : ?>
    <?php if ($popular_enabled_speedy_search) : ?>
      <div class="popular-searches">
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