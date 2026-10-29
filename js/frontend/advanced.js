jQuery(document).ready(function ($) {
  const { __, _x, _n, _nx } = wp.i18n;

  let selector = '.snappy-search-advanced-input';

  if (!$(selector).length) {
    return;
  }

  let default_result_type = snappy_search_object.options?.default_result_type ?? '';
  let characters          = snappy_search_object.options?.characters ?? 4;
  let typing_delay        = snappy_search_object.options?.typing_delay ?? 300;
  let posts_enabled       = snappy_search_object.options?.posts?.enabled ?? false;
  let pages_enabled       = snappy_search_object.options?.pages?.enabled ?? false;
  let products_enabled    = snappy_search_object.options?.products?.enabled ?? false;
  let downloads_enabled   = snappy_search_object.options?.downloads?.enabled ?? false;
  let currency            = snappy_search_object.currency ?? '$';
  let filters_enabled     = snappy_search_object.options?.advanced?.filters_enabled ?? false;
  let rating_filter_enabled = snappy_search_object.options?.filters_rating_enabled ?? false;
  let price_range_filter_enabled = snappy_search_object.options?.filters_price_range_enabled ?? false;
  let custom_field_filter_enabled = String(snappy_search_object.options?.filters_custom_fields ?? '').trim().length > 0;
  let category_filter_enabled = snappy_search_object.options?.filters_category_enabled ?? false;
  let attribute_filter_enabled = String(snappy_search_object.options?.filters_attributes ?? '').trim().length > 0;
  let acf_field_filter_enabled = String(snappy_search_object.options?.filters_acf_fields ?? '').trim().length > 0;
  let has_active_product_filters = rating_filter_enabled || price_range_filter_enabled || custom_field_filter_enabled || category_filter_enabled || attribute_filter_enabled || acf_field_filter_enabled;
  let catalog_enabled = !!snappy_search_object.options?.filters_catalog_enabled;
  let catalog_facets = null;
  let browse_results = {};
  let fresh_popup = true;
  let latest_loading = false;
  let opening_pending = false;
  let opening_container = null;
  let search_endpoint      = snappy_search_object.endpoints?.search ?? "/wp-json/speedy-search/v1/search/";
  let latest_endpoint      = snappy_search_object.endpoints?.latest ?? snappy_search_object.endpoints?.preload ?? "/wp-json/speedy-search/v1/latest/";
  let use_search_php       = snappy_search_object.endpoints?.has_custom_file ?? false;
  let latest_results      = {};

  const $searchInput      = $(selector);
  const $searchForm       = $searchInput.closest("form");
  const typingDelay       = typing_delay;
  const postTypes         = getTypes();
  const initialSearchForm = buildInitialSearchForm();

  init();
  
  function init() {
    listener();
    navigation();
    popular();
    close();
    initialSearch();
    preloadLatestResults();
  }

  function listener() {
    if (!$searchInput.length) return;

    $searchInput.each(function () {
      let $input = $(this);
      let $form = $input.closest("form");
      let $container = $form.closest(".speedy-search-container.advanced-search");
      let typingTimer;

      // Inject dynamic HTML only if tabs are being built dynamically
      if (!$container.find(".instant-search-wrapper").length) {
        $form.after(buildInitialSearchForm());
        placeUnderInputPopular($form);
      }

      initMobileFilterToggle($container);
      bindFilterEvents($container);
      toggleProductFiltersVisibility($container);

      $input.on("input", function () {
        clearTimeout(typingTimer);
        const query = $.trim($input.val());

        if (query.length >= characters) {
          typingTimer = setTimeout(function () {
            // $container.find(".instant-search-wrapper").show();
            performSearch(query, $container);
          }, typingDelay);
        } else {
          fresh_popup = true;

          if (catalogReady() && catalogFiltersActive($container.find('.snappy-product-filters'))) {
            fetchCatalogProducts($container);
          } else if (browse_results && Object.keys(browse_results).length) {
            latest_results = browse_results;
            showLatestResults($container);
          }
        }
      });

      $input.on("focus click", function () {
        const query = $.trim($input.val());

        if (query.length >= characters) {
          return;
        }

        fresh_popup = true;
        showLatestResults($container);
      });
    });
  }

  function navigation() {
    const $tabs = $('.instant-search-tabs .tab');
    const $sections = $('.instant-search-section');

    if (!$tabs.length) return; // No tabs to navigate

    $tabs.on('click', function () {
      const selectedType = $(this).data('type');

      // Set active tab
      $tabs.removeClass('active');
      $(this).addClass('active');

      // Show corresponding section, hide others
      $sections.each(function () {
        const sectionType = $(this).data('type');
        if (sectionType === selectedType) {
          $(this).show();
        } else {
          $(this).hide();
        }
      });

      let $container = $(this).closest(".speedy-search-container.advanced-search");
      toggleProductFiltersVisibility($container);
    });
  }

  function popular() {
    $(document).on("click", ".speedy-search-container .search-term", function(e) {
      let $popular = $(this).text();
      let $input = $(this).closest(".speedy-search-container").find(".snappy-search-advanced-input");

      if (!$input.length) {
        return;
      }

      $input.val($popular).trigger("input");
    });
  }
  
  function close() {
    $(document).on("click", function(e) {
      if (!$(e.target).closest('.speedy-search-container').length) {
        $(".speedy-search-container .instant-search-section").empty();

        $(".snappy-search-advanced-input").val('');
        $(".instant-search-wrapper").hide();
      }
    });

    $(".snappy-search-close").on("click", function(e) {
      $(".speedy-search-container .instant-search-section").empty();

      $(".snappy-search-advanced-input").val('');
      $(".instant-search-wrapper").hide();
    });
  }

  function initialSearch() {
    let $query      = $.trim($(".snappy-search-advanced-input").val());
    let $container = $(".speedy-search-container.advanced-search");
    
    if ($query.length >= characters) {
      performSearch($query, $container);
    }
  }

  function performSearch(query, $container) {
    opening_pending = false;

    if (fresh_popup) {
      clearProductFilters($container.find('.snappy-product-filters'));
      fresh_popup = false;
    }

    if (!use_search_php) {
      const $sections = $container.find('.instant-search-section');

      $sections.each(function () {
        let $result = $container.find(".instant-search-result");

        if ($result.length) {
          $result.addClass('skeleton-loading');
        } else {
          $container.find(".snappy-search-close").hide();
          $container.find(".loader").show();
        }
      });
    }

    fetchResults(query, $container);
  }

  function fetchResults(query, $container) {
    let started = Date.now();

    $.ajax({
      url: search_endpoint,
      data: { search: query },
      dataType: "json",
      success: function (data) {
        if (data && typeof data === 'object') {
          data.fetch_ms = Date.now() - started;
        }

        latest_results = data;
        if (use_search_php) {
          crossfadeResults($container, data);
        } else {
          applySearchFilters($container, data.products || []);
          renderSections($container, data);
        }
      },
      error: function () {
        const $sections = $container.find('.instant-search-section');

        $sections.each(function () {
          $(this).empty();
          $(this).append("<p>" + __("An error occurred while searching.", "speedy-search") + "</p>");
        });
      }
    })
    .always(function() {
      $(".speedy-search-container .instant-search-wrapper").show();
      $(".speedy-search-container .snappy-search-close").show();
      $(".speedy-search-container .loader").hide();
    });
  }

  function preloadLatestResults() {
    let started = Date.now();

    latest_loading = true;

    $.ajax({
      url: latest_endpoint,
      dataType: "json",
      success: function (data) {
        data = data || {};
        data.fetch_ms = Date.now() - started;
        rememberFacets(data);
        browse_results = data;
        latest_results = browse_results;
      },
      complete: function () {
        latest_loading = false;

        if (!opening_pending) {
          return;
        }

        if (latest_results && Object.keys(latest_results).length) {
          showLatestResults(opening_container);
          return;
        }

        opening_pending = false;

        if (opening_container && opening_container.length) {
          opening_container.find('.instant-search-section[data-type="product"]').first().html('<p>' + __('No results found.', 'speedy-search') + '</p>');
        }
      }
    });
  }

  function showLatestResults($container) {
    if (!latest_results || Object.keys(latest_results).length === 0) {
      if (latest_loading) {
        showOpeningSkeleton($container);
      }

      return;
    }

    opening_pending = false;
    applyBrowseFilters($container);
    renderSections($container, latest_results);

    $container.find(".instant-search-wrapper").show();
    $container.find(".snappy-search-close").show();
  }

  function showOpeningSkeleton($container) {
    opening_pending = true;
    opening_container = $container;

    let $section = $container.find('.instant-search-section[data-type="product"]').first();

    if ($section.length && !$section.find('.skeleton-loading').length) {
      $section.html(openingSkeletonHtml());
    }

    $container.find(".instant-search-wrapper").show();
    $container.find(".snappy-search-close").show();
  }

  function openingSkeletonHtml() {
    let card =
      '<div class="instant-search-result skeleton-loading">' +
        '<div class="permalink-result">' +
          '<div class="image-wrapper"></div>' +
          '<h2 class="title-result">&nbsp;</h2>' +
          '<p class="price-result">&nbsp;</p>' +
          '<p class="excerpt-result">&nbsp;</p>' +
        '</div>' +
        '<span class="product-action-result">&nbsp;</span>' +
      '</div>';

    return card + card + card + card;
  }

  function crossfadeResults($container, data) {
    let $results = $container.find('.instant-search-results');

    applySearchFilters($container, data.products || []);

    if (!$results.length) {
      renderSections($container, data);
      return;
    }

    $results.stop(true, true).fadeTo(75, 0.35, function () {
      renderSections($container, data);
      $results.fadeTo(150, 1);
    });
  }

  function renderSections($container, data) {
    const $sections = $container.find('.instant-search-section');
    let hasResults = false;
    let filteredProducts = getFilteredProducts($container, data.products || []);

    $sections.each(function () {
      let $section = $(this);
      let endpoint = $section.data('type');
      let endpointKey = endpoint + 's';
      let items = endpoint === 'product' ? filteredProducts : (Array.isArray(data[endpointKey]) ? data[endpointKey] : []);

      $section.empty();

      if (!items.length) {
        if (endpoint === 'product') {
          $section.append(productEmptyHtml($container));
        } else {
          $section.append("<p>" + __("No results found.", "speedy-search") + "</p>");
        }
        return;
      }

      hasResults = true;

      const results = $.map(items, function (item) {
        let imageHTML = "";
        let price = "";
        let rating = "";
        let featuredBadge = "";
        let stockBadge = "";
        let productAction = "";

        if (item.thumbnail) {
          imageHTML = '<img src="' + item.thumbnail + '" alt="' + item.title + '" class="image-result">';
        }

        if (item.price) {
          price = '<p class="price-result">' + currency + item.price + "</p>";
        }

        if (item.rating && endpoint === 'product') {
          rating = '<div class="rating-result"><div class="woocommerce">' + item.rating + "</div></div>";
        }

        if (item.is_featured && endpoint === 'product') {
          featuredBadge = '<div class="featured-badge">' + __("Featured", "speedy-search") + '</div>';
        }

        if (endpoint === 'product' && item.is_in_stock === false) {
          stockBadge = '<div class="stock-badge out-of-stock-badge">' + __("Out of stock", "speedy-search") + '</div>';
        }

        if (endpoint === 'product') {
          let actionLabel = item.is_variable ? __("Select Options", "speedy-search") : __("Add to Cart", "speedy-search");
          let actionUrl = item.is_variable ? item.permalink : getCurrentAddToCartUrl(item.id);
          productAction = `
            <a href="${actionUrl}" class="product-action-result">
              ${actionLabel}
            </a>
          `;
        }

        return `
          <div class="instant-search-result">
            <a href="${item.permalink}" class="permalink-result">
              <div class="image-wrapper">
                ${imageHTML}
                ${featuredBadge}
                ${stockBadge}
              </div>
              <div class="search-content">
                  <h2 class="title-result">${item.title}</h2>
                  ${rating}
                  ${price}
                  <p class="excerpt-result">${item.excerpt}</p>
              </div>
            </a>
            ${productAction}
          </div>
        `;
      }).join("");

      $section.append(results);
    });

    if (!hasResults && $sections.length) {
      let $first = $sections.first();

      if ($first.data('type') === 'product') {
        $first.html(productEmptyHtml($container));
      } else {
        $first.html('<p>' + __("No results found.", "speedy-search") + '</p>');
      }
    }

    updateFetchTime($container.find('.instant-search-results'), data);
  }

  function updateFetchTime($results, data) {
    if (!$results || !$results.length) {
      return;
    }

    $results.children('.snappy-fetch-time').remove();

    if (!snappy_search_object.options?.show_fetch_time || !data || typeof data.fetch_ms !== 'number') {
      return;
    }

    $results.append('<p class="snappy-fetch-time">' + __('Fetched in', 'speedy-search') + ' ' + data.fetch_ms + ' ms</p>');
  }

  function parsePrice(value) {
    let number = parseFloat(String(value).replace(/[^0-9.]/g, ''));
    return isNaN(number) ? 0 : number;
  }

  function getCurrentAddToCartUrl(productId) {
    if (!productId) {
      return window.location.href;
    }

    let currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('add-to-cart', String(productId));
    currentUrl.searchParams.delete('added-to-cart');

    return currentUrl.toString();
  }

  function setupProductFilters($container, products, mode) {
    if (!filters_enabled || !products_enabled || !has_active_product_filters) {
      return;
    }

    let $filters = $container.find('.snappy-product-filters');
    let fromFacets = mode === 'facets' && catalogReady();
    let shrink = mode === 'shrink' && catalogReady();
    let remembered = rememberFilterSelections($filters);

    if (fromFacets) {
      products = facetsToProducts(catalog_facets);
    }

    if (price_range_filter_enabled && !shrink) {
      if (fromFacets) {
        applyCatalogPriceBounds($filters, catalog_facets.price || {});
      } else {
      let prices = $.map(products, function (item) {
        return parsePrice(item.price);
      }).filter(function (price) {
        return price >= 0;
      });

      if (prices.length) {
        let minPrice = Math.floor(Math.min.apply(null, prices));
        let maxPrice = Math.ceil(Math.max.apply(null, prices));
        let $min = $filters.find('.filter-price-min');
        let $max = $filters.find('.filter-price-max');

        $min.attr('min', minPrice).attr('max', maxPrice).val(minPrice);
        $max.attr('min', minPrice).attr('max', maxPrice).val(maxPrice);
        $filters.data('min-price', minPrice);
        $filters.data('max-price', maxPrice);
        $filters.find('.filter-price-min-label').text(minPrice.toFixed(2));
        $filters.find('.filter-price-max-label').text(maxPrice.toFixed(2));
        updatePriceRangeTrack($filters, minPrice, maxPrice, minPrice, maxPrice);
      }
      }
    }

    if (category_filter_enabled) {
      populateCategoryFilters($filters, products);
    }

    if (attribute_filter_enabled) {
      populateProductAttributeFilters($filters, products);
    }

    if (custom_field_filter_enabled) {
      populateCustomFieldFilters($filters, products);
    }

    if (acf_field_filter_enabled) {
      populateAcfFieldFilters($filters, products);
    }

    if (shrink || fromFacets) {
      restoreFilterSelections($filters, remembered);
    }

    updateResetFiltersState($filters);
  }

  function catalogReady() {
    return catalog_enabled && !!catalog_facets;
  }

  function rememberFacets(data) {
    if (data && data.product_facets) {
      catalog_facets = data.product_facets;
    }
  }

  function facetsToProducts(facets) {
    return [{
      price: 0,
      categories: facets.categories || [],
      attributes: facets.attributes || {},
      custom_fields: facets.custom_fields || {},
      acf_fields: facets.acf_fields || {}
    }];
  }

  function applyBrowseFilters($container) {
    if (catalogReady()) {
      setupProductFilters($container, [], 'facets');
      return;
    }

    setupProductFilters($container, (browse_results.products || latest_results.products || []));
  }

  function applySearchFilters($container, products) {
    if (catalogReady()) {
      setupProductFilters($container, products, 'shrink');
      return;
    }

    setupProductFilters($container, products);
  }

  function applyCatalogPriceBounds($filters, price) {
    let minPrice = Math.floor(parsePrice(price.min));
    let maxPrice = Math.ceil(parsePrice(price.max));

    if (minPrice > maxPrice) {
      let swap = minPrice;
      minPrice = maxPrice;
      maxPrice = swap;
    }

    let $min = $filters.find('.filter-price-min');
    let $max = $filters.find('.filter-price-max');
    let ready = !!$filters.data('catalog-price-ready');

    $min.attr('min', minPrice).attr('max', maxPrice);
    $max.attr('min', minPrice).attr('max', maxPrice);

    if (!ready) {
      $min.val(minPrice);
      $max.val(maxPrice);
      $filters.data('catalog-price-ready', 1);
    }

    $filters.data('min-price', minPrice);
    $filters.data('max-price', maxPrice);
    $filters.find('.filter-price-min-label').text(parsePrice($min.val()).toFixed(2));
    $filters.find('.filter-price-max-label').text(parsePrice($max.val()).toFixed(2));
    updatePriceRangeTrack($filters, parsePrice($min.val()), parsePrice($max.val()), minPrice, maxPrice);
  }

  function collectCatalogFilters($filters) {
    let filters = {};
    let category = String($filters.find('.filter-product-category').val() || '').trim();

    if (category) {
      filters.category = category;
    }

    let rating = parseFloat($filters.find('.filter-rating').val() || '0');

    if (rating > 0) {
      filters.rating = rating;
    }

    $filters.find('.filter-product-attribute').each(function () {
      let selected = String($(this).val() || '').trim();
      let key = String($(this).data('attribute-key') || '');

      if (!selected || key === '') {
        return;
      }

      if (!filters.attributes) {
        filters.attributes = {};
      }

      filters.attributes[key] = selected;
    });

    $filters.find('.filter-custom-field').each(function () {
      let selected = String($(this).val() || '').trim();
      let key = normalizeCustomFieldKey($(this).data('field-key'));

      if (!selected || key === '') {
        return;
      }

      if (!filters.custom_fields) {
        filters.custom_fields = {};
      }

      filters.custom_fields[key] = selected;
    });

    $filters.find('.filter-acf-field').each(function () {
      let selected = String($(this).val() || '').trim();
      let key = normalizeCustomFieldKey($(this).data('field-key'));

      if (!selected || key === '') {
        return;
      }

      if (!filters.acf_fields) {
        filters.acf_fields = {};
      }

      filters.acf_fields[key] = selected;
    });

    if (price_range_filter_enabled && $filters.data('catalog-price-ready')) {
      let minBound = parsePrice($filters.data('min-price'));
      let maxBound = parsePrice($filters.data('max-price'));
      let minPrice = parsePrice($filters.find('.filter-price-min').val());
      let maxPrice = parsePrice($filters.find('.filter-price-max').val());

      if (minPrice !== minBound || maxPrice !== maxBound) {
        filters.min_price = minPrice;
        filters.max_price = maxPrice;
      }
    }

    return filters;
  }

  function catalogFiltersActive($filters) {
    return Object.keys(collectCatalogFilters($filters)).length > 0;
  }

  function updateResetFiltersState($filters) {
    if (!$filters || !$filters.length) {
      return;
    }

    $filters.find('.snappy-clear-filters-button, .snappy-reset-filters-button').prop('disabled', !catalogFiltersActive($filters));
  }

  function fetchCatalogProducts($container) {
    let $filters = $container.find('.snappy-product-filters');
    let $result = $container.find('.instant-search-result');
    let started = Date.now();

    if ($result.length) {
      $result.addClass('skeleton-loading');
    }

    $.ajax({
      url: latest_endpoint,
      data: { catalog_filters: JSON.stringify(collectCatalogFilters($filters)) },
      dataType: 'json',
      success: function (data) {
        data = data || {};
        data.fetch_ms = Date.now() - started;
        rememberFacets(data);
        browse_results = data;
        latest_results = browse_results;
        applyBrowseFilters($container);
        renderSections($container, latest_results);
        $container.find('.instant-search-wrapper').show();
        $container.find('.snappy-search-close').show();
      },
      error: function () {
        $result.removeClass('skeleton-loading');
      }
    });
  }

  function escapeFilterText(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function activeFilterLabels($filters) {
    let labels = [];
    let filters = collectCatalogFilters($filters);

    if (filters.category) {
      labels.push(__('Category', 'speedy-search') + ': ' + $filters.find('.filter-product-category option:selected').text());
    }

    if (filters.rating) {
      labels.push(__('Rating', 'speedy-search') + ': ' + $filters.find('.filter-rating option:selected').text());
    }

    if (filters.attributes) {
      $filters.find('.filter-product-attribute').each(function () {
        let selected = String($(this).val() || '').trim();

        if (!selected) {
          return;
        }

        let label = $(this).prev('label').text();
        labels.push(label + ': ' + $(this).find('option:selected').text());
      });
    }

    if (filters.custom_fields) {
      $filters.find('.filter-custom-field').each(function () {
        let selected = String($(this).val() || '').trim();

        if (!selected) {
          return;
        }

        labels.push($(this).prev('label').text() + ': ' + $(this).find('option:selected').text());
      });
    }

    if (filters.acf_fields) {
      $filters.find('.filter-acf-field').each(function () {
        let selected = String($(this).val() || '').trim();

        if (!selected) {
          return;
        }

        labels.push($(this).prev('label').text() + ': ' + $(this).find('option:selected').text());
      });
    }

    if (filters.min_price != null || filters.max_price != null) {
      labels.push(__('Price', 'speedy-search') + ': ' + $filters.find('.filter-price-min-label').text() + ' - ' + $filters.find('.filter-price-max-label').text());
    }

    return labels;
  }

  function clearProductFilters($filters) {
    $filters.find('select').val('');
    $filters.find('.filter-rating').val('0');

    if (!price_range_filter_enabled || !$filters.data('catalog-price-ready')) {
      return;
    }

    let minPrice = parsePrice($filters.data('min-price'));
    let maxPrice = parsePrice($filters.data('max-price'));
    $filters.find('.filter-price-min').val(minPrice);
    $filters.find('.filter-price-max').val(maxPrice);
    $filters.find('.filter-price-min-label').text(minPrice.toFixed(2));
    $filters.find('.filter-price-max-label').text(maxPrice.toFixed(2));
    updatePriceRangeTrack($filters, minPrice, maxPrice, minPrice, maxPrice);
  }

  function productEmptyHtml($container) {
    let query = $.trim($container.find(selector).val());
    let $filters = $container.find('.snappy-product-filters');
    let labels = activeFilterLabels($filters);

    if (catalogReady() && query.length >= characters && labels.length) {
      return '<p><a href="#" class="snappy-clear-search-filter">' + escapeFilterText(__('Clear search to filter all products by ', 'speedy-search') + labels.join(', ')) + '</a></p>';
    }

    if (catalogReady() && query.length < characters && labels.length) {
      return '<p>' + __('No products match these filters.', 'speedy-search') + ' <a href="#" class="snappy-clear-filters">' + __('Clear filters', 'speedy-search') + '</a></p>';
    }

    return '<p>' + __('No results found.', 'speedy-search') + '</p>';
  }

  function rememberFilterSelections($filters) {
    let remembered = {
      category: selectionOf($filters.find('.filter-product-category')),
      rating: selectionOf($filters.find('.filter-rating')),
      attributes: {},
      custom: {},
      acf: {}
    };

    $filters.find('.filter-product-attribute').each(function () {
      let key = String($(this).data('attribute-key') || '');

      if (key) {
        remembered.attributes[key] = selectionOf($(this));
      }
    });

    $filters.find('.filter-custom-field').each(function () {
      let key = normalizeCustomFieldKey($(this).data('field-key'));

      if (key) {
        remembered.custom[key] = selectionOf($(this));
      }
    });

    $filters.find('.filter-acf-field').each(function () {
      let key = normalizeCustomFieldKey($(this).data('field-key'));

      if (key) {
        remembered.acf[key] = selectionOf($(this));
      }
    });

    return remembered;
  }

  function selectionOf($select) {
    if (!$select.length) {
      return { value: '', text: '' };
    }

    return {
      value: String($select.val() || ''),
      text: $select.find('option:selected').text()
    };
  }

  function restoreFilterSelections($filters, remembered) {
    restoreSelect($filters.find('.filter-product-category'), remembered.category);
    restoreSelect($filters.find('.filter-rating'), remembered.rating);

    $.each(remembered.attributes, function (key, item) {
      let $select = $filters.find('.filter-product-attribute').filter(function () {
        return String($(this).data('attribute-key')) === String(key);
      });

      if (!$select.length && item.value) {
        $filters.find('.product-attribute-filters').append(
          '<label>' + escapeFilterText(key) + '</label>' +
          '<select class="filter-product-attribute" data-attribute-key="' + escapeFilterText(key) + '"></select>'
        );
        $select = $filters.find('.filter-product-attribute').filter(function () {
          return String($(this).data('attribute-key')) === String(key);
        });
      }

      restoreSelect($select, item);
    });

    $.each(remembered.custom, function (key, item) {
      restoreKeyedSelect($filters.find('.custom-field-filters'), '.filter-custom-field', key, item);
    });

    $.each(remembered.acf, function (key, item) {
      restoreKeyedSelect($filters.find('.acf-field-filters'), '.filter-acf-field', key, item);
    });
  }

  function restoreKeyedSelect($bucket, selectorClass, key, item) {
    let $select = $bucket.find(selectorClass).filter(function () {
      return normalizeCustomFieldKey($(this).data('field-key')) === key;
    });

    if (!$select.length && item.value) {
      $bucket.append(
        '<label>' + escapeFilterText(normalizeCustomFieldLabel(key)) + '</label>' +
        '<select class="' + selectorClass.replace('.', '') + '" data-field-key="' + escapeFilterText(key) + '"></select>'
      );
      $select = $bucket.find(selectorClass).filter(function () {
        return normalizeCustomFieldKey($(this).data('field-key')) === key;
      });
    }

    restoreSelect($select, item);
  }

  function restoreSelect($select, item) {
    if (!$select || !$select.length || !item || !item.value) {
      return;
    }

    let exists = false;

    $select.find('option').each(function () {
      if (String($(this).val()) === String(item.value)) {
        exists = true;
        return false;
      }
    });

    if (!exists) {
      $select.append($('<option>').val(item.value).text(item.text || item.value));
    }

    $select.val(item.value);
  }

  function getFilteredProducts($container, products) {
    if (!filters_enabled || !products_enabled || !has_active_product_filters || !products.length) {
      return products;
    }

    let $filters = $container.find('.snappy-product-filters');
    let minRating = rating_filter_enabled ? parseFloat($filters.find('.filter-rating').val() || '0') : 0;
    let minPrice = price_range_filter_enabled ? parsePrice($filters.find('.filter-price-min').val()) : 0;
    let maxPrice = price_range_filter_enabled ? parsePrice($filters.find('.filter-price-max').val()) : Number.MAX_SAFE_INTEGER;

    if (price_range_filter_enabled && minPrice > maxPrice) {
      let temp = minPrice;
      minPrice = maxPrice;
      maxPrice = temp;
    }

    return $.grep(products, function (item) {
      let rating = parseFloat(item.average_rating || 0);
      let price = parsePrice(item.price);
      let customFieldsMatch = custom_field_filter_enabled ? matchCustomFieldFilters($filters, item) : true;
      let categoryMatch = category_filter_enabled ? matchCategoryFilter($filters, item) : true;
      let attributeMatch = attribute_filter_enabled ? matchProductAttributeFilters($filters, item) : true;
      let acfFieldsMatch = acf_field_filter_enabled ? matchAcfFieldFilters($filters, item) : true;

      return rating >= minRating && price >= minPrice && price <= maxPrice && customFieldsMatch && categoryMatch && attributeMatch && acfFieldsMatch;
    });
  }

  function bindFilterEvents($container) {
    if (!filters_enabled || !products_enabled || !has_active_product_filters) {
      return;
    }

    $container.off('change.snappyFilters input.snappyFilters', '.snappy-product-filters input, .snappy-product-filters select');
    $container.on('change.snappyFilters input.snappyFilters', '.snappy-product-filters input, .snappy-product-filters select', function (event) {
      let $filters = $container.find('.snappy-product-filters');
      if (price_range_filter_enabled) {
        let minPrice = parsePrice($filters.find('.filter-price-min').val());
        let maxPrice = parsePrice($filters.find('.filter-price-max').val());
        let minBound = parsePrice($filters.data('min-price'));
        let maxBound = parsePrice($filters.data('max-price'));

        if (minPrice > maxPrice) {
          if ($(this).hasClass('filter-price-min')) {
            $filters.find('.filter-price-max').val(minPrice);
            maxPrice = minPrice;
          } else {
            $filters.find('.filter-price-min').val(maxPrice);
            minPrice = maxPrice;
          }
        }

        $filters.find('.filter-price-min-label').text(minPrice.toFixed(2));
        $filters.find('.filter-price-max-label').text(maxPrice.toFixed(2));
        updatePriceRangeTrack($filters, minPrice, maxPrice, minBound, maxBound);
      }

      updateResetFiltersState($filters);

      let query = $.trim($container.find(selector).val());
      let isPrice = $(this).hasClass('filter-price-min') || $(this).hasClass('filter-price-max');

      if (catalogReady() && query.length < characters) {
        if (isPrice && event.type === 'input') {
          return;
        }

        if (catalogFiltersActive($filters)) {
          fetchCatalogProducts($container);
        } else if (browse_results && Object.keys(browse_results).length) {
          latest_results = browse_results;
          applyBrowseFilters($container);
          renderSections($container, latest_results);
        }

        return;
      }

      renderSections($container, latest_results);
    });

    $container.off('click.snappyClearFilters', '.snappy-clear-filters');
    $container.on('click.snappyClearFilters', '.snappy-clear-filters', function (e) {
      e.preventDefault();
      clearProductFilters($container.find('.snappy-product-filters'));
      updateResetFiltersState($container.find('.snappy-product-filters'));
      fetchCatalogProducts($container);
    });

    $container.off('click.snappyClearSearch', '.snappy-clear-search-filter');
    $container.on('click.snappyClearSearch', '.snappy-clear-search-filter', function (e) {
      e.preventDefault();
      $container.find(selector).val('');
      fresh_popup = true;

      if (catalogFiltersActive($container.find('.snappy-product-filters'))) {
        fetchCatalogProducts($container);
      } else if (browse_results && Object.keys(browse_results).length) {
        latest_results = browse_results;
        showLatestResults($container);
      }
    });
  }

  function initMobileFilterToggle($container) {
    if (!filters_enabled || !products_enabled || !has_active_product_filters) {
      return;
    }

    let $filters = $container.find('.snappy-product-filters');

    if (!$filters.length) {
      return;
    }

    $filters.addClass('mobile-collapsible');
    $filters.removeClass('is-open');
    $filters.find('.product-filters-toggle').attr('role', 'button').attr('tabindex', '0').attr('aria-expanded', 'false');

    $container.off('click.snappyFilterToggle', '.product-filters-toggle');
    $container.on('click.snappyFilterToggle', '.product-filters-toggle', function () {
      let $panel = $(this).closest('.snappy-product-filters');
      let isOpen = $panel.hasClass('is-open');
      $panel.toggleClass('is-open', !isOpen);
      $(this).attr('aria-expanded', isOpen ? 'false' : 'true');
    });
  }

  function updatePriceRangeTrack($filters, minPrice, maxPrice, minBound, maxBound) {
    if (maxBound <= minBound) {
      $filters.find('.dual-range-fill').css({ left: '0%', right: '0%' });
      return;
    }

    let left = ((minPrice - minBound) / (maxBound - minBound)) * 100;
    let right = ((maxBound - maxPrice) / (maxBound - minBound)) * 100;

    $filters.find('.dual-range-fill').css({
      left: left + '%',
      right: right + '%'
    });
  }

  function populateCategoryFilters($filters, products) {
    let $select = $filters.find('.filter-product-category');

    if (!$select.length) {
      return;
    }

    let selected = String($select.val() || '');
    let byId = {};

    $.each(products, function (_, item) {
      if (!item.categories || !$.isArray(item.categories)) {
        return;
      }

      $.each(item.categories, function (_, cat) {
        if (!cat || cat.id == null) {
          return;
        }

        let id = String(cat.id);
        byId[id] = cat.name ? String(cat.name) : id;
      });
    });

    let ids = Object.keys(byId).sort(function (a, b) {
      return byId[a].localeCompare(byId[b]);
    });
    let options = '<option value="">' + __('All categories', 'speedy-search') + '</option>';

    $.each(ids, function (_, id) {
      let sel = selected === id ? ' selected' : '';
      options += '<option value="' + id + '"' + sel + '>' + byId[id] + '</option>';
    });

    $select.html(options);
  }

  function matchCategoryFilter($filters, item) {
    let selected = String($filters.find('.filter-product-category').val() || '').trim();

    if (!selected) {
      return true;
    }

    let cats = item.categories && $.isArray(item.categories) ? item.categories : [];
    let match = false;

    $.each(cats, function (_, cat) {
      if (cat && String(cat.id) === selected) {
        match = true;
        return false;
      }
    });

    return match;
  }

  function populateProductAttributeFilters($filters, products) {
    let $container = $filters.find('.product-attribute-filters');

    if (!$container.length) {
      return;
    }

    let selectedMap = {};

    $container.find('.filter-product-attribute').each(function () {
      selectedMap[String($(this).data('attribute-key'))] = $(this).val();
    });

    let fieldMap = {};

    $.each(products, function (_, item) {
      if (!item.attributes || typeof item.attributes !== 'object') {
        return;
      }

      $.each(item.attributes, function (attrKey, attrBlock) {
        if (!attrBlock || typeof attrBlock !== 'object' || !$.isArray(attrBlock.values)) {
          return;
        }

        let label = attrBlock.label ? String(attrBlock.label) : String(attrKey);

        if (!fieldMap[attrKey]) {
          fieldMap[attrKey] = { label: label, slugNames: {} };
        }

        $.each(attrBlock.values, function (_, v) {
          if (v && v.slug != null) {
            fieldMap[attrKey].slugNames[String(v.slug)] = v.name ? String(v.name) : String(v.slug);
          }
        });
      });
    });

    $container.empty();

    let orderedAttrKeys = Array.isArray(snappy_search_object.options?.filters_attributes_ordered_keys)
      ? snappy_search_object.options.filters_attributes_ordered_keys
      : [];
    let sortedKeys = [];
    let attrSeen = {};

    $.each(orderedAttrKeys, function (_, k) {
      let key = String(k);

      if (Object.prototype.hasOwnProperty.call(fieldMap, key) && !attrSeen[key]) {
        sortedKeys.push(key);
        attrSeen[key] = true;
      }
    });

    $.each(Object.keys(fieldMap).sort(function (a, b) {
      return fieldMap[a].label.localeCompare(fieldMap[b].label);
    }), function (_, k) {
      if (!attrSeen[k]) {
        sortedKeys.push(k);
        attrSeen[k] = true;
      }
    });

    $.each(sortedKeys, function (_, attrKey) {
      let label = fieldMap[attrKey].label;
      let slugNames = fieldMap[attrKey].slugNames;
      let slugs = Object.keys(slugNames).sort(function (a, b) {
        return slugNames[a].localeCompare(slugNames[b]);
      });
      let selected = selectedMap[attrKey] || '';
      let options = '<option value="">' + __('All', 'speedy-search') + '</option>';

      $.each(slugs, function (_, slug) {
        let sel = selected === slug ? ' selected' : '';
        options += '<option value="' + slug.replace(/"/g, '&quot;') + '"' + sel + '>' + slugNames[slug].replace(/</g, '&lt;') + '</option>';
      });

      $container.append(
        '<label>' + label.replace(/</g, '&lt;') + '</label>' +
        '<select class="filter-product-attribute" data-attribute-key="' + String(attrKey).replace(/"/g, '&quot;') + '">' +
          options +
        '</select>'
      );
    });
  }

  function matchProductAttributeFilters($filters, item) {
    let attrs = item.attributes && typeof item.attributes === 'object' ? item.attributes : {};
    let ok = true;

    $filters.find('.filter-product-attribute').each(function () {
      let selected = String($(this).val() || '').trim();
      let attrKey = String($(this).data('attribute-key') || '');

      if (!selected || attrKey === '') {
        return;
      }

      let block = attrs[attrKey];

      if (!block || !$.isArray(block.values)) {
        ok = false;
        return false;
      }

      let hit = false;

      $.each(block.values, function (_, v) {
        if (v && String(v.slug) === selected) {
          hit = true;
          return false;
        }
      });

      if (!hit) {
        ok = false;
        return false;
      }
    });

    return ok;
  }

  function normalizeCustomFieldKey(fieldKey) {
    return String(fieldKey || '').trim().toLowerCase();
  }

  function normalizeCustomFieldLabel(fieldKey) {
    return String(fieldKey || '')
      .replace(/^_+/, '')
      .replace(/_/g, ' ')
      .replace(/\b\w/g, function(char) {
        return char.toUpperCase();
      });
  }

  function normalizeCustomFieldValues(rawValue) {
    if (Array.isArray(rawValue)) {
      return $.grep($.map(rawValue, function (value) {
        return String(value || '').trim();
      }), function (value) {
        return !!value;
      });
    }

    let value = String(rawValue || '').trim();

    return value ? [value] : [];
  }

  function populateCustomFieldFilters($filters, products) {
    let fieldValues = {};
    let selectedValues = {};
    let $container = $filters.find('.custom-field-filters');

    if (!$container.length) {
      return;
    }

    $container.find('.filter-custom-field').each(function () {
      selectedValues[$(this).data('field-key')] = $(this).val();
    });

    $.each(products, function (_, item) {
      if (!item.custom_fields || typeof item.custom_fields !== 'object') {
        return;
      }

      $.each(item.custom_fields, function (rawKey, rawValue) {
        let key = normalizeCustomFieldKey(rawKey);
        let values = normalizeCustomFieldValues(rawValue);

        if (!key || !values.length) {
          return;
        }

        if (!fieldValues[key]) {
          fieldValues[key] = {};
        }

        $.each(values, function (_, value) {
          fieldValues[key][value] = true;
        });
      });
    });

    $container.empty();

    let orderedCfKeys = Array.isArray(snappy_search_object.options?.filters_custom_fields_ordered_keys)
      ? snappy_search_object.options.filters_custom_fields_ordered_keys
      : [];
    let fieldsToRender = [];
    let cfSeen = {};

    $.each(orderedCfKeys, function (_, k) {
      let key = normalizeCustomFieldKey(k);

      if (fieldValues[key] && Object.keys(fieldValues[key]).length && !cfSeen[key]) {
        fieldsToRender.push(key);
        cfSeen[key] = true;
      }
    });

    $.each(Object.keys(fieldValues).sort(), function (_, fieldKey) {
      if (!cfSeen[fieldKey] && fieldValues[fieldKey] && Object.keys(fieldValues[fieldKey]).length) {
        fieldsToRender.push(fieldKey);
        cfSeen[fieldKey] = true;
      }
    });

    $.each(fieldsToRender, function (_, fieldKey) {
      let values = fieldValues[fieldKey];
      let valueKeys = Object.keys(values).sort();

      if (!valueKeys.length) {
        return;
      }

      let label = normalizeCustomFieldLabel(fieldKey);
      let selected = selectedValues[fieldKey] || '';
      let options = '<option value="">' + __('All', 'speedy-search') + '</option>';

      $.each(valueKeys, function (_, value) {
        let selectedAttr = selected === value ? ' selected' : '';
        options += '<option value="' + value + '"' + selectedAttr + '>' + value + '</option>';
      });

      $container.append(
        '<label>' + label + '</label>' +
        '<select class="filter-custom-field" data-field-key="' + fieldKey + '">' +
          options +
        '</select>'
      );
    });
  }

  function matchCustomFieldFilters($filters, item) {
    let isMatch = true;
    let productCustomFields = item.custom_fields && typeof item.custom_fields === 'object' ? item.custom_fields : {};

    $filters.find('.filter-custom-field').each(function () {
      let selected = String($(this).val() || '').trim();
      let fieldKey = normalizeCustomFieldKey($(this).data('field-key'));
      let itemValues = normalizeCustomFieldValues(productCustomFields[fieldKey]);

      if (selected && $.inArray(selected, itemValues) === -1) {
        isMatch = false;
        return false;
      }
    });

    return isMatch;
  }

  function populateAcfFieldFilters($filters, products) {
    let fieldValues = {};
    let selectedValues = {};
    let $container = $filters.find('.acf-field-filters');

    if (!$container.length) {
      return;
    }

    $container.find('.filter-acf-field').each(function () {
      selectedValues[$(this).data('field-key')] = $(this).val();
    });

    $.each(products, function (_, item) {
      if (!item.acf_fields || typeof item.acf_fields !== 'object') {
        return;
      }

      $.each(item.acf_fields, function (rawKey, rawValue) {
        let key = normalizeCustomFieldKey(rawKey);
        let values = normalizeCustomFieldValues(rawValue);

        if (!key || !values.length) {
          return;
        }

        if (!fieldValues[key]) {
          fieldValues[key] = {};
        }

        $.each(values, function (_, value) {
          fieldValues[key][value] = true;
        });
      });
    });

    $container.empty();

    let orderedAcfKeys = Array.isArray(snappy_search_object.options?.filters_acf_fields_ordered_keys)
      ? snappy_search_object.options.filters_acf_fields_ordered_keys
      : [];
    let fieldsToRender = [];
    let acfSeen = {};

    $.each(orderedAcfKeys, function (_, k) {
      let key = normalizeCustomFieldKey(k);

      if (fieldValues[key] && Object.keys(fieldValues[key]).length && !acfSeen[key]) {
        fieldsToRender.push(key);
        acfSeen[key] = true;
      }
    });

    $.each(Object.keys(fieldValues).sort(), function (_, fieldKey) {
      if (!acfSeen[fieldKey] && fieldValues[fieldKey] && Object.keys(fieldValues[fieldKey]).length) {
        fieldsToRender.push(fieldKey);
        acfSeen[fieldKey] = true;
      }
    });

    $.each(fieldsToRender, function (_, fieldKey) {
      let values = fieldValues[fieldKey];
      let valueKeys = Object.keys(values).sort();

      if (!valueKeys.length) {
        return;
      }

      let label = normalizeCustomFieldLabel(fieldKey);
      let selected = selectedValues[fieldKey] || '';
      let options = '<option value="">' + __('All', 'speedy-search') + '</option>';

      $.each(valueKeys, function (_, value) {
        let selectedAttr = selected === value ? ' selected' : '';
        options += '<option value="' + value + '"' + selectedAttr + '>' + value + '</option>';
      });

      $container.append(
        '<label>' + label + '</label>' +
        '<select class="filter-acf-field" data-field-key="' + fieldKey + '">' +
          options +
        '</select>'
      );
    });
  }

  function matchAcfFieldFilters($filters, item) {
    let isMatch = true;
    let productAcfFields = item.acf_fields && typeof item.acf_fields === 'object' ? item.acf_fields : {};

    $filters.find('.filter-acf-field').each(function () {
      let selected = String($(this).val() || '').trim();
      let fieldKey = normalizeCustomFieldKey($(this).data('field-key'));
      let itemValues = normalizeCustomFieldValues(productAcfFields[fieldKey]);

      if (selected && $.inArray(selected, itemValues) === -1) {
        isMatch = false;
        return false;
      }
    });

    return isMatch;
  }

  function toggleProductFiltersVisibility($container) {
    if (!filters_enabled || !products_enabled || !has_active_product_filters) {
      return;
    }

    let $filters = $container.find('.snappy-product-filters');

    if (!$filters.length) {
      return;
    }

    let activeType = '';
    let $activeTab = $container.find('.instant-search-tabs .tab.active');

    if ($activeTab.length) {
      activeType = $activeTab.data('type');
    } else {
      let $visibleSection = $container.find('.instant-search-section:visible').first();
      activeType = $visibleSection.data('type');
    }

    if (activeType === 'product') {
      $filters.show();
    } else {
      $filters.hide();
    }
  }

  function popularPosition() {
    let position = snappy_search_object.options?.popular?.position || 'under_input';

    if (position !== 'above_tabs' && position !== 'below_tabs' && position !== 'below_results') {
      return 'under_input';
    }

    return position;
  }

  function popularHeadingHtml(popular) {
    let heading = Object.prototype.hasOwnProperty.call(popular, 'heading')
      ? popular.heading
      : __('Popular Searches', 'speedy-search');

    if (!heading) {
      return '';
    }

    return '<p class="popular">' + escapeFilterText(String(heading)) + '</p>';
  }

  function popularMarkup() {
    let popular = snappy_search_object.options?.popular || {};

    if (!popular.enabled) {
      return '';
    }

    let terms = snappy_search_object.popular;

    if (!Array.isArray(terms) || !terms.length) {
      return '';
    }

    let links = '';

    $.each(terms, function (_, term) {
      if (!term || !term.term) {
        return;
      }

      links += '<a href="javascript:void(0);" class="search-term">' + escapeFilterText(String(term.term)) + '</a>';
    });

    if (!links) {
      return '';
    }

    return '<div class="popular-searches">' + popularHeadingHtml(popular) + links + '</div>';
  }

  function popularSlotHtml(slot, hasTabs) {
    let position = popularPosition();

    if (position === 'under_input') {
      return '';
    }

    if (position === 'below_tabs' && !hasTabs) {
      position = 'above_tabs';
    }

    if (position !== slot) {
      return '';
    }

    return popularMarkup();
  }

  function placeUnderInputPopular($form) {
    if (popularPosition() !== 'under_input' || !$form || !$form.length) {
      return;
    }

    if ($form.parent().children('.popular-searches').length) {
      return;
    }

    let html = popularMarkup();

    if (!html) {
      return;
    }

    $form.after(html);
  }

  function buildInitialSearchForm() {
    if (postTypes.length === 0) return ''; // nothing enabled

    const showTabs = postTypes.length > 1;
    const firstType = postTypes[0].type;

    let tabsHTML = '';
    let sectionsHTML = '';

    if (showTabs) {
      tabsHTML = '<ul class="instant-search-tabs">\n';

      $.each(postTypes, function(i, t) {
        tabsHTML += '  <li class="tab' + (i === 0 ? ' active' : '') + '" data-type="' + t.type + '">' + t.label + '</li>\n';
      });

      tabsHTML += '</ul>\n';
    }

    if (postTypes.length > 1) {
      $.each(postTypes, function(i, t) {
        let isActive = t.type === firstType;
        let displayStyle = isActive ? 'block' : 'none';
        let label = __('Search ' + t.type + ' you are looking for.', 'speedy-search');

        sectionsHTML += `
          <div class="instant-search-section" data-type="${t.type}" style="display: ${displayStyle};">
            <p>${label}</p>
          </div>
        `;
      });
    } else {
      sectionsHTML += `
        <div class="instant-search-section" data-type="${firstType}">
        </div>
      `;
    }

    let filtersHTML = '';

    if (filters_enabled && products_enabled && has_active_product_filters) {
      filtersHTML = `
        <div class="snappy-product-filters">
          <h4 class="product-filters-toggle">
            <span>${__('Filter Products', 'speedy-search')}</span>
            <span class="product-filters-caret" aria-hidden="true">
              <i class="fas fa-chevron-down toggle-caret-down"></i>
              <i class="fas fa-chevron-up toggle-caret-up"></i>
            </span>
          </h4>
          <button type="button" class="snappy-clear-filters snappy-clear-filters-button" disabled>${__('Reset Filters', 'speedy-search')}</button>
          <div class="product-filters-body">
          ${price_range_filter_enabled ? `
          <label>${__('Price Range', 'speedy-search')}</label>
          <div class="dual-range-slider">
            <div class="dual-range-track"></div>
            <div class="dual-range-fill"></div>
            <input type="range" class="filter-price-min" step="1" value="0">
            <input type="range" class="filter-price-max" step="1" value="0">
          </div>
          <p class="price-range-text"><span class="filter-price-min-label">0.00</span> - <span class="filter-price-max-label">0.00</span></p>` : ''}
          ${rating_filter_enabled ? `
          <label>${__('Rating', 'speedy-search')}</label>
          <select class="filter-rating">
            <option value="0">${__('All ratings', 'speedy-search')}</option>
            <option value="5">5.0</option>
            <option value="4">4.0+</option>
            <option value="3">3.0+</option>
            <option value="2">2.0+</option>
            <option value="1">1.0+</option>
          </select>` : ''}
          ${category_filter_enabled ? `
          <label>${__('Category', 'speedy-search')}</label>
          <select class="filter-product-category">
            <option value="">${__('All categories', 'speedy-search')}</option>
          </select>` : ''}
          ${attribute_filter_enabled ? '<div class="product-attribute-filters"></div>' : ''}
          ${custom_field_filter_enabled ? '<div class="custom-field-filters"></div>' : ''}
          ${acf_field_filter_enabled ? '<div class="acf-field-filters"></div>' : ''}
          <button type="button" class="snappy-clear-filters snappy-reset-filters-button" disabled>${__('Reset Filters', 'speedy-search')}</button>
          </div>
        </div>
      `;
    }

    let popularAbove = popularSlotHtml('above_tabs', showTabs);
    let popularBelowTabs = popularSlotHtml('below_tabs', showTabs);
    let popularBelowResults = popularSlotHtml('below_results', showTabs);

    let searchForm = `
      <div class="instant-search-wrapper" style="display: none;">
        ${popularAbove}
        ${tabsHTML}
        ${popularBelowTabs}
        <div class="instant-search-layout">
          ${filtersHTML}
          <div class="instant-search-results">
            ${sectionsHTML}
            ${popularBelowResults}
          </div>
        </div>
      </div>
    `;

    return searchForm;
  }

  function getTypes() {
    let types = [];

    if (products_enabled) {
      types.push({ type: 'product', label: __('Products', 'speedy-search') });
    }

    if (downloads_enabled) {
      types.push({ type: 'download', label: __('Downloads', 'speedy-search') });
    }

    if (posts_enabled) {
      types.push({ type: 'post', label: __('Posts', 'speedy-search') });
    }
    
    if (pages_enabled) {
      types.push({ type: 'page', label: __('Pages', 'speedy-search') });
    }

    // Sort so the default_result_type appears first
    if (default_result_type) {
      types.sort(function (a, b) {
        if (a.type === default_result_type) return -1;
        if (b.type === default_result_type) return 1;
        return 0;
      });
    }

    return types;
  }
});