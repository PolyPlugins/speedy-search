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
  let rating_filter_enabled = snappy_search_object.options?.filters_rating_enabled ?? false;
  let price_range_filter_enabled = snappy_search_object.options?.filters_price_range_enabled ?? false;
  let custom_field_filter_enabled = String(snappy_search_object.options?.filters_custom_fields ?? '').trim().length > 0;
  let category_filter_enabled = snappy_search_object.options?.filters_category_enabled ?? false;
  let attribute_filter_enabled = String(snappy_search_object.options?.filters_attributes ?? '').trim().length > 0;
  let has_active_product_filters = rating_filter_enabled || price_range_filter_enabled || custom_field_filter_enabled || category_filter_enabled || attribute_filter_enabled;
  let search_endpoint         = snappy_search_object.endpoints?.search ?? "/wp-json/speedy-search/v1/search/";
  let latest_endpoint         = snappy_search_object.endpoints?.latest ?? snappy_search_object.endpoints?.preload ?? "/wp-json/speedy-search/v1/latest/";
  let use_search_php          = snappy_search_object.endpoints?.has_custom_file ?? false;
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
    preloadLatestResults();
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

      initMobileFilterToggle($container);
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

      $input.on("focus click", function () {
        const query = $.trim($input.val());

        if (query.length >= characters) {
          return;
        }

        showLatestResults($container);
      });
    });
  }

  function navigation() {
    $(document).off('click.snappyAdvNavStack', '.speedy-search-container.advanced-search .instant-search-tabs .tab');
    $(document).on('click.snappyAdvNavStack', '.speedy-search-container.advanced-search .instant-search-tabs .tab', function () {
      let $container = $(this).closest('.speedy-search-container.advanced-search');
      const $tabs = $container.find('.instant-search-tabs .tab');
      const $sections = $container.find('.instant-search-section');
      const selectedType = $(this).data('type');

      $tabs.removeClass('active');
      $(this).addClass('active');

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
    $(document).on("click.snappyAdvCloseOutsideStack", function(e) {
      if (!$(e.target).closest('.speedy-search-container.advanced-search').length) {
        let $adv = $(".speedy-search-container.advanced-search");

        $adv.find(".instant-search-section").empty();
        $adv.find(".snappy-search-advanced-input").val('');
        $adv.find(".instant-search-wrapper").hide();
      }
    });

    $(document).on("click.snappyAdvCloseBtnStack", ".speedy-search-container.advanced-search .snappy-search-close", function(e) {
      let $container = $(this).closest(".speedy-search-container.advanced-search");

      $container.find(".instant-search-section").empty();
      $container.find(".snappy-search-advanced-input").val('');
      $container.find(".instant-search-wrapper").hide();
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

        // $section.html('<p>' + __("Searching " + type + "...", "speedy-search") + '</p>');
      });
    }

    fetchResults(query, $container);
  }

  function fetchResults(query, $container) {
    $.ajax({
      url: search_endpoint,
      data: { search: query },
      dataType: "json",
      success: function (data) {
        latest_results = data;
        if (use_search_php) {
          crossfadeResults($container, data);
        } else {
          setupProductFilters($container, data.products || []);
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
      $container.find(".instant-search-wrapper").show();
      $container.find(".snappy-search-close").show();
      $container.find(".loader").hide();

      let allEmpty = true;

      $container.find(".instant-search-section").each(function () {
        if ($(this).find('.instant-search-result').length > 0) {
          allEmpty = false;

          return false;
        }
      });

      if (allEmpty) {
        $container.find(".instant-search-section").first().html('<p>' + __("No results found.", "speedy-search") + '</p>');
      }
    });
  }

  function preloadLatestResults() {
    $.ajax({
      url: latest_endpoint,
      dataType: "json",
      success: function (data) {
        latest_results = data || {};
      },
    });
  }

  function showLatestResults($container) {
    if (!latest_results || Object.keys(latest_results).length === 0) {
      return;
    }

    setupProductFilters($container, latest_results.products || []);
    renderSections($container, latest_results);

    $container.find(".instant-search-wrapper").show();
    $container.find(".snappy-search-close").show();
  }

  function crossfadeResults($container, data) {
    let $results = $container.find('.instant-search-results');

    setupProductFilters($container, data.products || []);

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

        // Add class to hide results after the limit
        const hiddenClass = index >= initialLimit ? ' hidden-result' : '';

        return `
          <div class="instant-search-result grid-item${hiddenClass}">
            <a href="${item.permalink}" class="permalink-result">
              ${imageHTML ? `<div class="image-wrapper">${imageHTML}${featuredBadge}${stockBadge}</div>` : ''}
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
            ${productAction}
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

  function getCurrentAddToCartUrl(productId) {
    if (!productId) {
      return window.location.href;
    }

    let currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('add-to-cart', String(productId));
    currentUrl.searchParams.delete('added-to-cart');

    return currentUrl.toString();
  }

  function setupProductFilters($container, products) {
    if (!filters_enabled || !products_enabled || !has_active_product_filters) {
      return;
    }

    let $filters = $container.find('.snappy-product-filters');

    if (price_range_filter_enabled) {
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

    if (category_filter_enabled) {
      populateCategoryFilters($filters, products);
    }

    if (attribute_filter_enabled) {
      populateProductAttributeFilters($filters, products);
    }

    if (custom_field_filter_enabled) {
      populateCustomFieldFilters($filters, products);
    }
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

      return rating >= minRating && price >= minPrice && price <= maxPrice && customFieldsMatch && categoryMatch && attributeMatch;
    });
  }

  function bindFilterEvents($container) {
    if (!filters_enabled || !products_enabled || !has_active_product_filters) {
      return;
    }

    $container.off('change.snappyFilters input.snappyFilters', '.snappy-product-filters input, .snappy-product-filters select');
    $container.on('change.snappyFilters input.snappyFilters', '.snappy-product-filters input, .snappy-product-filters select', function () {
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

      renderSections($container, latest_results);
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
          </div>
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