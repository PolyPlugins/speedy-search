<?php
use PolyPlugins\Speedy_Search\Backend\DB;
use PolyPlugins\Speedy_Search\Utils;

$popular_options = Utils::get_option('popular');
$popular_enabled = isset($popular_options['enabled']) ? $popular_options['enabled'] : 0;
?>

<div class="speedy-search-container">
  <form role="search" method="get" class="snappy-search-form" action="<?php echo esc_url(home_url('/')); ?>">
    <input type="text" class="snappy-search-input" placeholder="<?php echo esc_attr($atts['placeholder']); ?>" autocomplete="off" name="s">
    <button type="button" class="snappy-search-close" aria-label="Close Search">×</button>
  </form>
  <?php if ($popular_enabled) : ?>
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