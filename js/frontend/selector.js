jQuery(document).ready(function ($) {
  const { __, _x, _n, _nx } = wp.i18n;

  let selector = snappy_search_object.options?.selector ?? '';

  if (!selector) {
    return;
  }

  if (!$(selector).length) {
    return;
  }

  let default_result_type   = snappy_search_object.options?.default_result_type ?? '';
  let characters            = snappy_search_object.options?.characters ?? 4;
  let typing_delay          = snappy_search_object.options?.typing_delay ?? 300;
  let posts_enabled         = snappy_search_object.options?.posts?.enabled ?? false;
  let posts_tab_enabled     = snappy_search_object.options?.posts?.tab_enabled ?? true;
  let pages_enabled         = snappy_search_object.options?.pages?.enabled ?? false;
  let pages_tab_enabled     = snappy_search_object.options?.pages?.tab_enabled ?? true;
  let products_enabled      = snappy_search_object.options?.products?.enabled ?? false;
  let products_tab_enabled  = snappy_search_object.options?.products?.tab_enabled ?? true;
  let downloads_enabled     = snappy_search_object.options?.downloads?.enabled ?? false;
  let downloads_tab_enabled = snappy_search_object.options?.downloads?.tab_enabled ?? true;
  let popular               = snappy_search_object.popular ?? false;
  let currency              = snappy_search_object.currency ?? '$';
  let filters_enabled       = snappy_search_object.options?.selector_filters_enabled ?? false;
  let latest_results        = {};

  const $searchInput        = $(selector);
  const $searchForm         = $searchInput.closest("form");
  const typingDelay         = typing_delay;
  const postTypes           = getTypes();
  const initialSearchForm   = buildInitialSearchForm();

  init();
  
  function init() {
    listener();
    navigation();
    popular();
    renderPopularTerms();
  }

  function listener() {
    let typingTimer;

    if (!$searchInput.length || !$searchForm.length) return;

    $searchForm.after(initialSearchForm);
    bindFilterEvents();
    toggleProductFiltersVisibility();

    $searchInput.on("input", function () {
      clearTimeout(typingTimer);
      const query = $.trim($searchInput.val());

      if (query.length >= characters) {
        typingTimer = setTimeout(function () {
          performSearch(query);
        }, typingDelay);
      } else {
        // Change text to type more characters
      }
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

      toggleProductFiltersVisibility();
    });
  }

  function popular() {
    $(document).on("click", ".speedy-search-container .search-term", function(e) {
      let $popular = $(this).text();

      $(".snappy-search-form .snappy-search-input").val($popular);
      $(".snappy-search-form .snappy-search-input").val($popular).trigger("input");
    });
  }

  function performSearch(query) {
    // Show "Searching..." in each existing section
    $('.instant-search-section').each(function () {
      $(this).html('<p>' + __("Searching...", "speedy-search") + '</p>');
    });

    fetchResults(query);
  }

  function fetchResults(query) {
    $.ajax({
      url: "/wp-json/speedy-search/v1/search/",
      data: { search: query },
      dataType: "json",
      success: function (data) {
        latest_results = data;
        setupProductFilters(data.products || []);
        renderSections(data);
      },
      error: function () {
        $('.instant-search-section').each(function () {
          $(this).empty();
          $(this).append(
            "<p>" +
              __("An error occurred while searching.", "speedy-search") +
            "</p>"
          );
        });
      },
    });
  }

  function renderSections(data) {
    let hasResults = false;
    let filteredProducts = getFilteredProducts(data.products || []);

    $('.instant-search-section').each(function () {
      let endpoint = $(this).data('type');
      let endpointKey = endpoint + 's';
      let items = endpoint === 'product' ? filteredProducts : (Array.isArray(data[endpointKey]) ? data[endpointKey] : []);

      $(this).empty();

      if (!items.length) {
        $(this).append("<p>" + __("No results found.", "speedy-search") + "</p>");
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
          imageHTML =
            '<img src="' +
            item.thumbnail +
            '" alt="' +
            item.title +
            '" class="image-result">';
        }

        if (item.price) {
          price =
            '<p class="price-result">' + currency + item.price + "</p>";
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
          productAction =
            '<a href="' +
            actionUrl +
            '" class="product-action-result">' +
            actionLabel +
            '</a>';
        }

        return (
          '<div class="instant-search-result">' +
          '<a href="' +
          item.permalink +
          '" class="permalink-result">' +
          '<div class="image-wrapper">' +
          imageHTML +
          featuredBadge +
          stockBadge +
          '</div>' +
          '<h2 class="title-result">' +
          item.title +
          "</h2>" +
          rating +
          price +
          '<p class="excerpt-result">' +
          item.excerpt +
          "</p>" +
          "</a>" +
          productAction +
          "</div>"
        );
      }).join("");

      $(this).append(results);
    });

    if (!hasResults && $('.instant-search-section').length) {
      $('.instant-search-section').first().html("<p>" + __("No results found.", "speedy-search") + "</p>");
    }
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

  function setupProductFilters(products) {
    if (!filters_enabled || !products_enabled) {
      return;
    }

    let prices = $.map(products, function (item) {
      return parsePrice(item.price);
    }).filter(function (price) {
      return price >= 0;
    });

    if (!prices.length) {
      return;
    }

    let minPrice = Math.floor(Math.min.apply(null, prices));
    let maxPrice = Math.ceil(Math.max.apply(null, prices));
    let $filters = $('.snappy-product-filters');
    let $min = $filters.find('.filter-price-min');
    let $max = $filters.find('.filter-price-max');

    $min.attr('min', minPrice).attr('max', maxPrice).val(minPrice);
    $max.attr('min', minPrice).attr('max', maxPrice).val(maxPrice);
    $filters.data('min-price', minPrice);
    $filters.data('max-price', maxPrice);
    $filters.find('.filter-price-min-label').text(minPrice.toFixed(2));
    $filters.find('.filter-price-max-label').text(maxPrice.toFixed(2));
    updatePriceRangeTrack($filters, minPrice, maxPrice, minPrice, maxPrice);
    populateCustomFieldFilters($filters, products);
  }

  function getFilteredProducts(products) {
    if (!filters_enabled || !products_enabled || !products.length) {
      return products;
    }

    let $filters = $('.snappy-product-filters');
    let minRating = parseFloat($filters.find('.filter-rating').val() || '0');
    let minPrice = parsePrice($filters.find('.filter-price-min').val());
    let maxPrice = parsePrice($filters.find('.filter-price-max').val());

    if (minPrice > maxPrice) {
      let temp = minPrice;
      minPrice = maxPrice;
      maxPrice = temp;
    }

    return $.grep(products, function (item) {
      let rating = parseFloat(item.average_rating || 0);
      let price = parsePrice(item.price);
      let customFieldsMatch = matchCustomFieldFilters($filters, item);

      return rating >= minRating && price >= minPrice && price <= maxPrice && customFieldsMatch;
    });
  }

  function bindFilterEvents() {
    if (!filters_enabled || !products_enabled) {
      return;
    }

    $(document).off('change.snappyFilters input.snappyFilters', '.snappy-product-filters input, .snappy-product-filters select');
    $(document).on('change.snappyFilters input.snappyFilters', '.snappy-product-filters input, .snappy-product-filters select', function () {
      let $filters = $('.snappy-product-filters');
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

      renderSections(latest_results);
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
        let value = String(rawValue || '').trim();

        if (!key || !value) {
          return;
        }

        if (!fieldValues[key]) {
          fieldValues[key] = {};
        }

        fieldValues[key][value] = true;
      });
    });

    $container.empty();

    $.each(fieldValues, function (fieldKey, values) {
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
      let itemValue = String(productCustomFields[fieldKey] || '').trim();

      if (selected && itemValue !== selected) {
        isMatch = false;
        return false;
      }
    });

    return isMatch;
  }

  function toggleProductFiltersVisibility() {
    if (!filters_enabled || !products_enabled) {
      return;
    }

    let $filters = $('.snappy-product-filters');

    if (!$filters.length) {
      return;
    }

    let activeType = '';
    let $activeTab = $('.instant-search-tabs .tab.active');

    if ($activeTab.length) {
      activeType = $activeTab.data('type');
    } else {
      let $visibleSection = $('.instant-search-section:visible').first();
      activeType = $visibleSection.data('type');
    }

    if (activeType === 'product') {
      $filters.show();
    } else {
      $filters.hide();
    }
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

    $.each(postTypes, function(i, t) {
      const isActive = t.type === firstType;
      sectionsHTML +=
        '<div class="instant-search-section" data-type="' + t.type + '" style="display: ' + (isActive ? 'block' : 'none') + ';">\n' +
          '  <p>' + __('Search ' + t.type + ' you are looking for.', 'speedy-search') + '</p>\n' +
        '</div>\n';
    });

    let popularHTML = '';

    if (Array.isArray(popular) && popular.length > 0) {
      popularHTML = `
        <div class="popular-searches">
          <p class="popular">` + __('Popular Searches', 'speedy-search') + `</p>
          <div class="popular-terms"></div>
        </div>
      `;
    }

    let filtersHTML = '';

    if (filters_enabled && products_enabled) {
      filtersHTML = `
        <div class="snappy-product-filters">
          <h4>${__('Filter Products', 'speedy-search')}</h4>
          <label>${__('Rating', 'speedy-search')}</label>
          <select class="filter-rating">
            <option value="0">${__('All ratings', 'speedy-search')}</option>
            <option value="5">5.0</option>
            <option value="4">4.0+</option>
            <option value="3">3.0+</option>
            <option value="2">2.0+</option>
            <option value="1">1.0+</option>
          </select>
          <div class="custom-field-filters"></div>
          <label>${__('Price Range', 'speedy-search')}</label>
          <div class="dual-range-slider">
            <div class="dual-range-track"></div>
            <div class="dual-range-fill"></div>
            <input type="range" class="filter-price-min" step="1" value="0">
            <input type="range" class="filter-price-max" step="1" value="0">
          </div>
          <p class="price-range-text"><span class="filter-price-min-label">0.00</span> - <span class="filter-price-max-label">0.00</span></p>
        </div>
      `;
    }

    let searchForm = `
      <div class="instant-search-wrapper">
        ${popularHTML}
        ${tabsHTML}
        <div class="instant-search-layout">
          ${filtersHTML}
          <div class="instant-search-results">
            ${sectionsHTML}
          </div>
        </div>
      </div>
    `;

    return searchForm;
  }

  function renderPopularTerms() {
    const $container = $(".popular-terms");

    if (!$container.length || !Array.isArray(snappy_search_object.popular)) return;

    snappy_search_object.popular.forEach(term => {
      const $term = $('<a href="javascript:void(0);" class="search-term"></a>').text(term.term);
      $container.append($term);
    });

    // Handle clicks on the rendered terms
    $(document).on("click", ".search-term", function () {
      const selectedTerm = $(this).text();

      $searchInput
        .val(selectedTerm)
        .trigger("input"); // Triggers the existing listener for search
    });
  }

  function getTypes() {
    let types = [];

    if (products_enabled && products_tab_enabled) {
      types.push({ type: 'product', label: __('Products', 'speedy-search') });
    }

    if (downloads_enabled && downloads_tab_enabled) {
      types.push({ type: 'download', label: __('Downloads', 'speedy-search') });
    }

    if (posts_enabled && posts_tab_enabled) {
      types.push({ type: 'post', label: __('Posts', 'speedy-search') });
    }

    if (pages_enabled && pages_tab_enabled) {
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
