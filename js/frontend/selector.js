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
      let $resultType = $(this).data('type');

      // Find the matching post type object
      let typeObj = postTypes.find(function (t) {
        return t.type === $resultType;
      });

      // Fallback if not found
      let label = typeObj ? typeObj.label : $resultType;

      $(this).html('<p>' + __("Searching " + $resultType + "...", "speedy-search") + '</p>');

      fetchResults(query, $resultType, label);
    });
  }

  function fetchResults(query, endpoint, label) {
    $.ajax({
      url: "/wp-json/speedy-search/v1/" + endpoint + "s/",
      data: { search: query },
      dataType: "json",
      success: function (data) {
        if (!data.length) {
          $('.instant-search-section[data-type="' + endpoint + '"]').empty();
          $('.instant-search-section[data-type="' + endpoint + '"]').append(
            "<p>" + __("No results found.", "speedy-search") + "</p>"
          );
        } else {
          const results = $.map(data, function (item) {
            let imageHTML = "";
            let price = "";

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

            return (
              '<div class="instant-search-result">' +
              '<a href="' +
              item.permalink +
              '" class="permalink-result">' +
              imageHTML +
              '<h2 class="title-result">' +
              item.title +
              "</h2>" +
              price +
              '<p class="excerpt-result">' +
              item.excerpt +
              "</p>" +
              "</a>" +
              "</div>"
            );
          }).join("");

          $('.instant-search-section[data-type="' + endpoint + '"]').empty();
          $('.instant-search-section[data-type="' + endpoint + '"]')
            .append(results);
        }
      },
      error: function (xhr, status, error) {
        $('.instant-search-section[data-type="' + endpoint + '"]').empty();
        $('.instant-search-section[data-type="' + endpoint + '"]')
          .append(
            "<p>" +
              __("An error occurred while searching.", "speedy-search") +
            "</p>"
          );
      },
    });
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

    let searchForm = `
      <div class="instant-search-wrapper">
        ${popularHTML}
        ${tabsHTML}
        <div class="instant-search-results">
          ${sectionsHTML}
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
