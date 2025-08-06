

jQuery(document).ready(function ($) {
  const { __, _x, _n, _nx } = wp.i18n;

  let selector          = '.snappy-search-input';

  if (!$(selector).length) {
    return;
  }

  let default_result_type   = snappy_search_object.options?.default_result_type ?? '';
  let characters            = snappy_search_object.options?.characters ?? 4;
  let typing_delay          = snappy_search_object.options?.typing_delay ?? 300;
  let posts_enabled         = snappy_search_object.options?.posts?.enabled ?? false;
  let posts_tab_enabled     = snappy_search_object.options?.posts?.tab_enabled ?? false;
  let pages_enabled         = snappy_search_object.options?.pages?.enabled ?? false;
  let pages_tab_enabled     = snappy_search_object.options?.pages?.tab_enabled ?? false;
  let products_enabled      = snappy_search_object.options?.products?.enabled ?? false;
  let products_tab_enabled  = snappy_search_object.options?.products?.tab_enabled ?? false;
  let downloads_enabled     = snappy_search_object.options?.downloads?.enabled ?? false;
  let downloads_tab_enabled = snappy_search_object.options?.downloads?.tab_enabled ?? false;
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
    close();
  }

  function listener() {
    let typingTimer;

    if (!$searchInput.length) return;

    $searchInput.each(function () {
      let $input = $(this);
      let $form = $input.closest("form");
      let $container = $form.closest(".speedy-search-container");

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
    $(document).on("click", ".speedy-search-container .search-term", function(e) {
      let $popular = $(this).text();

      $(".snappy-search-form .snappy-search-input").val($popular);
      $(".snappy-search-form .snappy-search-input").val($popular).trigger("input");
    });
  }
  
  function close() {
    $(".snappy-search-close").on("click", function(e) {
      $(".speedy-search-container .instant-search-section").empty();

      $(".snappy-search-input").val('');
      $(".instant-search-wrapper").hide();
    });
  }

  function performSearch(query, $container) {
    const $sections = $container.find('.instant-search-section');

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

        if (!data.length) {
          $section.append("<p>" + __("No results found.", "speedy-search") + "</p>");
        } else {
          const results = $.map(data, function (item) {
            let imageHTML = "";
            let price = "";

            if (item.thumbnail) {
              imageHTML = '<img src="' + item.thumbnail + '" alt="' + item.title + '" class="image-result">';
            }

            if (item.price) {
              price = '<p class="price-result">' + currency + item.price + "</p>";
            }

            return `
              <div class="instant-search-result">
                <a href="${item.permalink}" class="permalink-result">
                  <div class="image-wrapper">
                    ${imageHTML}
                  </div>
                  <div class="search-content">
                      <h2 class="title-result">${item.title}</h2>
                      ${price}
                      <p class="excerpt-result">${item.excerpt}</p>
                  </div>
                </a>
              </div>
            `;
          }).join("");

          $section.append(results);
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

    let searchForm = `
      <div class="instant-search-wrapper" style="display: none;">
        ${tabsHTML}
        <div class="instant-search-results">
          ${sectionsHTML}
        </div>
      </div>
    `;

    return searchForm;
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