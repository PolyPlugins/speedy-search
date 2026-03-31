jQuery(document).ready(function ($) {
  const { __, _x, _n, _nx } = wp.i18n;

  let selector = '.snappy-search-advanced-input';

  if (!$(selector).length) {
    return;
  }

  let characters             = snappy_search_object.options?.characters ?? 4;
  let typing_delay           = snappy_search_object.options?.typing_delay ?? 300;
  let posts_enabled          = snappy_search_object.options?.posts?.enabled ?? false;
  let pages_enabled          = snappy_search_object.options?.pages?.enabled ?? false;
  let products_enabled       = snappy_search_object.options?.products?.enabled ?? false;
  let downloads_enabled      = snappy_search_object.options?.downloads?.enabled ?? false;
  let advanced_enabled_types = snappy_search_object.options?.advanced?.enabled_types ?? [];
  let currency               = snappy_search_object.currency ?? '$';
  let filters_enabled        = snappy_search_object.options?.advanced?.filters_enabled ?? false;
  let latest_results         = {};

  const $searchInput      = $(selector);
  const $searchForm       = $searchInput.closest("form");
  const typingDelay       = typing_delay;
  const postTypes         = getTypes();
  const initialSearchForm = buildInitialSearchForm();

  let firstenabled = postTypes.length ? postTypes[0].type : '';
  let default_result_type = snappy_search_object.options?.default_result_type ?? firstenabled;

  init();
  
  function init() {
    listener();
    navigation();
    popular();
    close();
    initialSearch();
  }

  function listener() {
    let typingTimer;

    if (!$searchInput.length) return;

    $searchInput.each(function () {
      let $input = $(this);
      let $form = $input.closest("form");
      let $container = $form.closest(".speedy-search-container.advanced-search");

      // Inject dynamic HTML only if tabs are being built dynamically
      if (!$container.find(".instant-search-wrapper").length) {
        $form.after(buildInitialSearchForm());
      }

      bindFilterEvents($container);

      $input.on("input", function () {
        clearTimeout(typingTimer);
        const query = $.trim($input.val());

        if (query.length >= characters) {
          typingTimer = setTimeout(function () {
            // $container.find(".instant-search-wrapper").show();
            performSearch(query, $container);
          }, typingDelay);
        } else {
          // Could show a message like: "Keep typing..."
          // $container.find(".instant-search-wrapper").hide();
        }
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
    });
  }

  function popular() {
    $(document).on("click", ".speedy-search-container.advanced-search.stacked .search-term", function(e) {
      let $popular = $(this).text();

      $(".speedy-search-container.advanced-search.stacked .snappy-search-form .snappy-search-advanced-input").val($popular);
      $(".speedy-search-container.advanced-search.stacked .snappy-search-form .snappy-search-advanced-input").val($popular).trigger("input");
    });
  }
  
  function close() {
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
    const $sections = $container.find('.instant-search-section');

    $sections.each(function () {
      let $result = $(".speedy-search-container .instant-search-result");

      if ($result.length) {
        $result.addClass('skeleton-loading');
      } else {
        $(".speedy-search-container .snappy-search-close").hide();
        $(".speedy-search-container .loader").show();
      }

      // $section.html('<p>' + __("Searching " + type + "...", "speedy-search") + '</p>');
    });

    fetchResults(query, $container);
  }

  function fetchResults(query, $container) {
    $.ajax({
      url: "/wp-json/speedy-search/v1/search/",
      data: { search: query },
      dataType: "json",
      success: function (data) {
        latest_results = data;
        setupProductFilters($container, data.products || []);
        renderSections($container, data);
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

      let allEmpty = true;
      
      $(".advanced-search .instant-search-section").each(function () {
        if ($(this).find('.instant-search-result').length > 0) {
          allEmpty = false;

          return false;
        }
      });

      if (allEmpty) {
        $(".advanced-search .instant-search-section").first().html('<p>' + __("No results found.", "speedy-search") + '</p>');
      }
    });
  }

  function renderSections($container, data) {
    const $sections = $container.find('.instant-search-section');
    let filteredProducts = getFilteredProducts($container, data.products || []);

    $sections.each(function () {
      let $section = $(this);
      let endpoint = $section.data('type');
      let endpointKey = endpoint + 's';
      let items = endpoint === 'product' ? filteredProducts : (Array.isArray(data[endpointKey]) ? data[endpointKey] : []);

      $section.empty();

      if (!items.length) {
        $section.append("<p>" + __("No results found.", "speedy-search") + "</p>");
        return;
      }

      const isDefaultType = endpoint === default_result_type;
      const initialLimit = isDefaultType ? items.length : 4;
      const showMore = !isDefaultType && items.length > initialLimit;

      const results = $.map(items, function (item, index) {
        let imageHTML = "";
        let price = "";
        let rating = "";

        if (item.thumbnail) {
          imageHTML = '<img src="' + item.thumbnail + '" alt="' + item.title + '" class="image-result">';
        }

        if (item.price) {
          price = '<p class="price-result">' + currency + item.price + "</p>";
        }

        if (item.rating && endpoint === 'product') {
          rating = '<div class="rating-result"><div class="woocommerce">' + item.rating + "</div></div>";
        }

        // Add class to hide results after the limit
        const hiddenClass = index >= initialLimit ? ' hidden-result' : '';

        return `
          <div class="instant-search-result grid-item${hiddenClass}">
            <a href="${item.permalink}" class="permalink-result">
              ${imageHTML ? `<div class="image-wrapper">${imageHTML}</div>` : ''}
              <div class="search-content">
                <h2 class="title-result">${item.title}</h2>
                ${rating}
                ${price}
                <p class="excerpt-result">${item.excerpt}</p>
                ${
                endpoint === 'post' ? `
                  <div class="read-more">
                    ${__("Read more >", "speedy-search")}
                  </div>` : ''
                }
              </div>
            </a>
          </div>
        `;
      }).join("");

      $section.append(results);

      if (showMore) {
        const readMoreBtn = `
          <div class="instant-search-result grid-item more-results">
            <button class="show-all-results">${__("Show all " + endpoint + " results", "speedy-search")}</button>
          </div>
        `;
        $section.append(readMoreBtn);
      }
    });
  }

  function parsePrice(value) {
    let number = parseFloat(String(value).replace(/[^0-9.]/g, ''));
    return isNaN(number) ? 0 : number;
  }

  function setupProductFilters($container, products) {
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
    let $filters = $container.find('.snappy-product-filters');
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

  function getFilteredProducts($container, products) {
    if (!filters_enabled || !products_enabled || !products.length) {
      return products;
    }

    let $filters = $container.find('.snappy-product-filters');
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

      return rating >= minRating && price >= minPrice && price <= maxPrice;
    });
  }

  function bindFilterEvents($container) {
    if (!filters_enabled || !products_enabled) {
      return;
    }

    $container.off('change.snappyFilters input.snappyFilters', '.snappy-product-filters input, .snappy-product-filters select');
    $container.on('change.snappyFilters input.snappyFilters', '.snappy-product-filters input, .snappy-product-filters select', function () {
      let $filters = $container.find('.snappy-product-filters');
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

      renderSections($container, latest_results);
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

  function buildInitialSearchForm() {
    if (postTypes.length === 0) return ''; // nothing enabled

    let sectionsHTML = '';

    $.each(postTypes, function(i, t) {

      sectionsHTML += `
        <div class="instant-search-section grid" data-type="${t.type}">
        </div>
      `;
    });

    let filtersHTML = '';

    if (filters_enabled && products_enabled) {
      filtersHTML = `
        <div class="snappy-product-filters">
          <h4>${__('Filter Products', 'speedy-search')}</h4>
          <label>${__('Rating', 'speedy-search')}</label>
          <select class="filter-rating">
            <option value="0">${__('All ratings', 'speedy-search')}</option>
            <option value="1">1.0+</option>
            <option value="2">2.0+</option>
            <option value="3">3.0+</option>
            <option value="4">4.0+</option>
            <option value="5">5.0</option>
          </select>
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
      <div class="instant-search-wrapper" style="display: none;">
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

  function getTypes() {
    let types = [];

    let enabledTypes = Array.isArray(advanced_enabled_types) ? advanced_enabled_types : [];

    if (posts_enabled && (enabledTypes.length === 0 || enabledTypes.includes('posts'))) {
      types.push({ type: 'post', label: __('Posts', 'speedy-search') });
    }

    if (pages_enabled && (enabledTypes.length === 0 || enabledTypes.includes('pages'))) {
      types.push({ type: 'page', label: __('Pages', 'speedy-search') });
    }

    if (products_enabled && (enabledTypes.length === 0 || enabledTypes.includes('products'))) {
      types.push({ type: 'product', label: __('Products', 'speedy-search') });
    }

    if (downloads_enabled && (enabledTypes.length === 0 || enabledTypes.includes('downloads'))) {
      types.push({ type: 'download', label: __('Downloads', 'speedy-search') });
    }

    return types;
  }

  // Show hidden results on button click
  $(document).on('click', '.show-all-results', function () {
    const $hiddenResults = $(this).closest('.instant-search-section');

    $hiddenResults.find('.hidden-result').removeClass('hidden-result');

    $(this).closest('.more-results').hide();
  });

});