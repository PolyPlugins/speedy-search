<?php
use PolyPlugins\Speedy_Search\Backend\DB;
use PolyPlugins\Speedy_Search\Utils;

$is_indexing          = Utils::is_indexing();
$popular_options      = Utils::get_option('popular');
$popular_enabled      = isset($popular_options['enabled']) ? $popular_options['enabled'] : 0;
$advanced_options     = Utils::get_option('advanced');
$advanced_placeholder = isset($advanced_options['placeholder']) ? $advanced_options['placeholder'] : 'Search...';
$advanced_enabled     = isset($advanced_options['enabled']) ? $advanced_options['enabled'] : 0;
$action               = $advanced_enabled && !$is_indexing ? home_url('/advanced-search/') : home_url('/');
$name                 = $advanced_enabled && !$is_indexing ? 'search' : 's';
$search               = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

get_header();
?>

<div class="speedy-search-container advanced-search">
  <form role="search" method="get" class="snappy-search-form" action="<?php echo esc_url($action); ?>">
    <input type="text" class="snappy-search-advanced-input" placeholder="<?php echo esc_attr($advanced_placeholder); ?>" autocomplete="off" name="<?php echo esc_html($name); ?>" value="<?php echo esc_html($search); ?>">
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

<?php get_footer(); ?>