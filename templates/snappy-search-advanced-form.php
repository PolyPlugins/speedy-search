<?php
use PolyPlugins\Speedy_Search\Backend\DB;
use PolyPlugins\Speedy_Search\Utils;

$is_indexing        = Utils::is_indexing();
$popular_options    = Utils::get_option('popular');
$popular_enabled    = isset($popular_options['enabled']) ? $popular_options['enabled'] : 0;
$advanced_options   = Utils::get_option('advanced');
$advanced_enabled   = isset($advanced_options['enabled']) ? $advanced_options['enabled'] : 0;
$advanced_page_slug = Utils::get_page_slug_by_template();
$action             = $advanced_enabled && $advanced_page_slug && !$is_indexing ? home_url('/' . $advanced_page_slug . '/') : home_url('/');
$name               = $advanced_enabled && $advanced_page_slug && !$is_indexing ? 'search' : 's';
?>

<div class="speedy-search-container advanced">
  <form role="search" method="get" class="snappy-search-form" action="<?php echo esc_url($action); ?>">
    <input type="text" class="snappy-search-input" placeholder="<?php echo esc_attr($atts['placeholder']); ?>" autocomplete="off" name="<?php echo esc_html($name); ?>">
    <button type="button" class="snappy-search-close" aria-label="Close Search">×</button>
    <span class="loader" style="display: none;"></span>
  </form>
  
  <?php if (!$is_indexing) : ?>
    <?php if ($popular_enabled) : ?>
      <div class="popular-searches">
        <p class="popular"><?php esc_attr_e('Popular Searches', 'speedy-search'); ?></p>
        <?php
        $popular = DB::get_top_terms_last_x_days();
        ?>
        <?php foreach ($popular as $term) : ?>
          <a href="javascript:void(0);" class="search-term"><?php echo esc_html($term['term']); ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>