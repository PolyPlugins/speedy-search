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
  let ajaxCalls              = 0;

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

    ajaxCalls = $sections.length;

    $sections.each(function () {
      let $section = $(this);
      let type = $section.data('type');

      let typeObj = postTypes.find(t => t.type === type);
      let label = typeObj ? typeObj.label : type;

      
      let $result = $(".speedy-search-container .instant-search-result");

      if ($result.length) {
        $result.addClass('skeleton-loading');
      } else {
        $(".speedy-search-container .snappy-search-close").hide();
        $(".speedy-search-container .loader").show();
      }

      // $section.html('<p>' + __("Searching " + type + "...", "speedy-search") + '</p>');

      fetchResults(query, type, label, $section);
    });
  }

  function fetchResults(query, endpoint, label, $section) {
    $.ajax({
      url: "/wp-json/speedy-search/v1/" + endpoint + "s/",
      data: { search: query },
      dataType: "json",
      success: function (data) {
        $section.empty();

        const isDefaultType = endpoint === default_result_type;
        const initialLimit = isDefaultType ? data.length : 4;
        const showMore = !isDefaultType && data.length > initialLimit;

        const results = $.map(data, function (item, index) {
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
      },
      error: function (xhr, status, error) {
        $section.empty();
        $section.append("<p>" + __("An error occurred while searching.", "speedy-search") + "</p>");
      }
    })
    .always(function() {
      $(".speedy-search-container .instant-search-wrapper").show();
      $(".speedy-search-container .snappy-search-close").show();
      $(".speedy-search-container .loader").hide();

      ajaxCalls--;

      if (ajaxCalls === 0) {
        let allEmpty = true;
        
        $(".advanced-search .instant-search-section").each(function () {
          if ($(this).children().length > 0) {
            allEmpty = false;

            return false;
          }
        });

        if (allEmpty) {
          $(".advanced-search .instant-search-section").first().html('<p>' + __("No results found.", "speedy-search") + '</p>');
        }
      }
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

    let searchForm = `
      <div class="instant-search-wrapper" style="display: none;">
        <div class="instant-search-results">
          ${sectionsHTML}
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