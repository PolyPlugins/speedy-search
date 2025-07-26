jQuery(document).ready(function($) {

  let options            = snappy_search_object.options;
  let popular_enabled    = options?.popular?.enabled ?? false;
  let delay              = options?.popular?.delay ?? 3000;
  let characters         = options?.popular?.characters ?? 3;
  let ajax_url           = snappy_search_analytics_object.ajax_url;
  let nonce              = snappy_search_analytics_object.nonce;

  let typingTimer;

  init();

  function init() {
    query();
  }

  function query() {
    selector();
    shortcode();
  }

  function selector() {
    $(document).on('input', '#ocean-search-form-1', function() {
      query_counts(this);
    });
  }

  function shortcode() {
    $(document).on('input', '.snappy-search-input', function() {
      query_counts(this);
    });
  }

  function query_counts(input) {
    if (popular_enabled) {
      clearTimeout(typingTimer);

      const query = $(input).val().trim();

      if (query.length >= characters) {
        typingTimer = setTimeout(function() {
          // Get counts for each type
          let result_counts = {};

          if ($('.instant-search-section').length) {
            $('.instant-search-section').each(function() {
              const type = $(this).data('type');
              const count = $(this).find('.instant-search-result').length;
              result_counts[type] = count;
            });

            // Send AJAX request
            $.post(ajax_url, {
              action: 'speedy_search_query',
              term: query,
              result_counts: result_counts,
              nonce: nonce
            });
          }
        }, delay);
      }
    }
  }

});
