let selector          = '#post-search-input';
let characters        = speedy_search_object.options?.characters ?? 4;
let typing_delay      = speedy_search_object.options?.typing_delay ?? 300;
let products_endpoint = speedy_search_object.endpoints?.admin_products ?? '/wp-json/speedy-search-search/v1/admin-products';
let unavailableLabel  = 'Error';

jQuery(document).ready(function ($) {
  if (!selector) return;

  const $productList = $('#the-list');
  const $searchInput = $(selector);
  const typingDelay  = typing_delay;
  let activeQuery = '';
  let activePage = 1;
  let activeTotalPages = 1;

  init();

  function init() {
    listener();
    listNav();
  }

  function listener() {
    let typingTimer;

    if (!$searchInput.length) return;

    $searchInput.on('input', function () {
      clearTimeout(typingTimer);
      const query = $.trim($searchInput.val());

      if (query === '') {
        // The replaced rows are not the list they had open, so load that page again.
        if (activeQuery) {
          window.location.reload();
        }
        return;
      }

      if (query.length >= characters) {
        typingTimer = setTimeout(function () {
          performSearch(query);
        }, typingDelay);
      }
    });
  }

  function performSearch(query) {
    showSkeleton();
    fetchResults(query, 1);
  }

  function showSkeleton() {
    if (!$productList.length) {
      return;
    }

    $productList.children('tr').children('td').each(function () {
      var $cell = $(this);
      var $values = $cell.contents().filter(function () {
        if (this.nodeType === 3) {
          return $.trim(this.nodeValue) !== '';
        }

        if (this.nodeType !== 1) {
          return false;
        }

        var $node = $(this);

        // Row actions are positioned off-screen and would pull the name bar away from the title.
        return !$node.hasClass('hidden') && !$node.hasClass('row-actions') && !$node.hasClass('toggle-row');
      });

      if ($cell.children('.snappy-skeleton').length || !$values.length) {
        return;
      }

      $values.wrapAll('<span class="snappy-skeleton"></span>');
    });

    $productList.addClass('skeleton-loading');
  }

  function hideSkeleton() {
    $productList.find('.snappy-skeleton').each(function () {
      $(this).contents().unwrap();
    });

    $productList.removeClass('skeleton-loading');
  }

  function fetchResults(query, page) {
    activeQuery = query;
    activePage = page || 1;

    $.ajax({
      url: products_endpoint,
      data: { search: query, page: activePage },
      dataType: 'json',
      beforeSend: function (xhr) {
        xhr.setRequestHeader('X-WP-Nonce', speedy_search_object.nonce);
      },
      success: function (data) {
        hideSkeleton();

        var products = $.isArray(data) ? data : (data && data.products ? data.products : []);
        var total = data && !$.isArray(data) && typeof data.total !== 'undefined' ? parseInt(data.total, 10) : products.length;
        var perPage = data && !$.isArray(data) && data.per_page ? parseInt(data.per_page, 10) : (products.length || 1);
        var current = data && !$.isArray(data) && data.page ? parseInt(data.page, 10) : 1;

        updateListNav(total, perPage, current);

        if (!products.length) {
          $productList.empty();

          Swal.fire({
            title: 'Error!',
            text: 'No results found!',
            icon: 'error'
          });
          return;
        }

        const columns = getProductColumns();

        $productList.empty();

        products.forEach(function (product) {
          $productList.append(renderProductRow(product, columns));
        });
      },
      error: function () {
        Swal.fire({
          title: 'Error!',
          text: 'A server error occurred, reverted to original products.',
          icon: 'error'
        });

        hideSkeleton();
      }
    });
  }

  function listNav() {
    $(document).on('click', '.tablenav-pages a[data-page]', function (event) {
      if (!activeQuery) {
        return;
      }

      event.preventDefault();

      var page = parseInt($(this).attr('data-page'), 10);

      if (!page || page === activePage) {
        return;
      }

      showSkeleton();
      fetchResults(activeQuery, page);
    });

    $(document).on('keydown', '.tablenav-pages .current-page', function (event) {
      if (!activeQuery || event.key !== 'Enter') {
        return;
      }

      event.preventDefault();
      $(this).trigger('change');
    });

    $(document).on('change', '.tablenav-pages .current-page', function () {
      if (!activeQuery) {
        return;
      }

      var page = parseInt($(this).val(), 10);

      if (!page || page < 1) {
        page = 1;
      }

      if (page > activeTotalPages) {
        page = activeTotalPages;
      }

      showSkeleton();
      fetchResults(activeQuery, page);
    });
  }

  function updateListNav(total, perPage, page) {
    if (!perPage || perPage < 1) {
      perPage = 1;
    }

    if (isNaN(total) || total < 0) {
      total = 0;
    }

    activeTotalPages = total > 0 ? Math.ceil(total / perPage) : 1;
    activePage = page > 0 ? page : 1;

    if (activePage > activeTotalPages) {
      activePage = activeTotalPages;
    }

    var countLabel = total.toLocaleString() + (total === 1 ? ' item' : ' items');

    $('.tablenav-pages').each(function () {
      var $nav = $(this);
      var $links = $nav.find('.pagination-links').first();
      var $paging = $links.children('.paging-input');

      $nav.toggleClass('one-page', activeTotalPages < 2);
      $nav.find('.displaying-num').text(countLabel);
      $nav.find('.total-pages').text(activeTotalPages.toLocaleString());
      $nav.find('.current-page').val(activePage);

      $nav.find('.tablenav-paging-text').each(function () {
        var node = this.firstChild;

        if (node && node.nodeType === 3 && $.trim(node.nodeValue).indexOf('of') !== 0) {
          node.nodeValue = activePage + ' of ';
        }
      });

      if (!$paging.length) {
        return;
      }

      $links.children().not($paging).remove();
      $paging.before(navButton(activePage > 1, 'first-page', 1, 'First page', '&laquo;') + navButton(activePage > 1, 'prev-page', activePage - 1, 'Previous page', '&lsaquo;'));
      $paging.after(navButton(activePage < activeTotalPages, 'next-page', activePage + 1, 'Next page', '&rsaquo;') + navButton(activePage < activeTotalPages, 'last-page', activeTotalPages, 'Last page', '&raquo;'));
    });
  }

  function navButton(enabled, className, page, label, symbol) {
    if (!enabled) {
      return '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">' + symbol + '</span>';
    }

    return '<a class="' + className + ' button" href="#" data-page="' + page + '"><span class="screen-reader-text">' + label + '</span><span aria-hidden="true">' + symbol + '</span></a>';
  }

  function formatDate(dateStr) {
    var d = new Date(dateStr);

    if (isNaN(d.getTime())) {
      return unavailableLabel;
    }

    return d.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    });
  }

  function getProductColumns() {
    const columns = [];

    $('table.wp-list-table thead tr:first-child').find('th, td').each(function () {
      const $cell = $(this);
      const className = $cell.attr('class') || '';
      const classes = className.split(/\s+/);
      const columnClass = classes.find(function (c) {
        return c.indexOf('column-') === 0;
      });

      if ($cell.hasClass('check-column')) {
        columns.push({
          type: 'check',
          hidden: $cell.hasClass('hidden')
        });
        return;
      }

      if (!columnClass) {
        return;
      }

      const key = columnClass.replace('column-', '');
      const label = $.trim($cell.find('span').first().text()) || $.trim($cell.text()) || key;

      columns.push({
        type: 'data',
        key: key,
        label: label,
        hidden: $cell.hasClass('hidden')
      });
    });

    return columns;
  }

  function renderProductRow(product, columns) {
    const productId = escapeHtml(product.id ?? '');
    const status = escapeHtml(product.status || 'publish');
    let row = `<tr id="post-${productId}" class="iedit author-self level-0 post-${productId} type-product status-${status}">`;

    columns.forEach(function (column) {
      if (column.type === 'check') {
        row += `
          <th scope="row" class="check-column">
            <input id="cb-select-${productId}" type="checkbox" name="post[]" value="${productId}">
          </th>
        `;
        return;
      }

      row += renderProductCell(column, product);
    });

    row += '</tr>';

    return row;
  }

  function renderProductCell(column, product) {
    const hiddenClass = column.hidden ? ' hidden' : '';
    const cellClass = `${column.key} column-${column.key}${hiddenClass}`;
    const colname = escapeHtml(column.label || column.key);
    const editLink = escapeHtml(product.edit_link || '');
    const title = escapeHtml(product.title || unavailableLabel);
    const productDate = product.date ? formatDate(product.date) : unavailableLabel;

    switch (column.key) {
      case 'thumb':
        if (!product.thumbnail) {
          return `<td class="${cellClass}" data-colname="${colname}"></td>`;
        }

        return `
          <td class="${cellClass}" data-colname="${colname}">
            <a href="${editLink}"><img src="${escapeHtml(product.thumbnail)}" alt="" width="40" height="40"></a>
          </td>
        `;
      case 'name':
        return `
          <td class="${cellClass} has-row-actions column-primary" data-colname="${colname}">
            <strong><a class="row-title" href="${editLink}">${title}</a></strong>
            <button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
          </td>
        `;
      case 'sku':
        return `<td class="${cellClass}" data-colname="${colname}">${escapeHtml(product.sku || '')}</td>`;
      case 'price':
        return `<td class="${cellClass}" data-colname="${colname}">${product.price_html || ''}</td>`;
      case 'is_in_stock':
        return `<td class="${cellClass}" data-colname="${colname}">${escapeHtml(product.stock_label || '')}</td>`;
      case 'product_cat':
        return `<td class="${cellClass}" data-colname="${colname}">${escapeHtml(product.categories || '')}</td>`;
      case 'featured':
        if (!product.featured) {
          return `<td class="${cellClass}" data-colname="${colname}"></td>`;
        }

        return `<td class="${cellClass}" data-colname="${colname}"><span class="dashicons dashicons-star-filled"></span></td>`;
      case 'date':
        return `
          <td class="${cellClass}" data-colname="${colname}">
            <time datetime="${escapeHtml(product.date || '')}">${escapeHtml(productDate)}</time>
          </td>
        `;
      default:
        return `<td class="${cellClass}" data-colname="${colname}"></td>`;
    }
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

});
