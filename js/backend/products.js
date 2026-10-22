let selector         = '#post-search-input';
let characters       = speedy_search_object.options?.characters ?? 4;
let typing_delay     = speedy_search_object.options?.typing_delay ?? 300;
let products_endpoint = speedy_search_object.endpoints?.admin_products ?? '/wp-json/speedy-search-search/v1/admin-products';
let unavailableLabel = 'Error';

jQuery(document).ready(function ($) {
  if (!selector) return;

  const $productList = $('#the-list');
  const $searchInput = $(selector);
  const typingDelay  = typing_delay;

  init();

  function init() {
    listener();
  }

  function listener() {
    let typingTimer;

    if (!$searchInput.length) return;

    $searchInput.on('input', function () {
      clearTimeout(typingTimer);
      const query = $.trim($searchInput.val());

      if (query.length >= characters) {
        typingTimer = setTimeout(function () {
          performSearch(query);
        }, typingDelay);
      }
    });
  }

  function performSearch(query) {
    if ($productList.length) {
      $productList.addClass('skeleton-loading');
    }

    fetchResults(query);
  }

  function fetchResults(query) {
    $.ajax({
      url: products_endpoint,
      data: { search: query },
      dataType: 'json',
      beforeSend: function (xhr) {
        xhr.setRequestHeader('X-WP-Nonce', speedy_search_object.nonce);
      },
      success: function (data) {
        $productList.removeClass('skeleton-loading');

        if (!data || !data.length) {
          Swal.fire({
            title: 'Error!',
            text: 'No results found!',
            icon: 'error'
          });
          return;
        }

        const columns = getProductColumns();

        $productList.empty();

        data.forEach(function (product) {
          $productList.append(renderProductRow(product, columns));
        });
      },
      error: function () {
        Swal.fire({
          title: 'Error!',
          text: 'A server error occurred, reverted to original products.',
          icon: 'error'
        });

        $productList.removeClass('skeleton-loading');
      }
    });
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
        return `<td class="${cellClass}" data-colname="${colname}">${escapeHtml(product.sku || unavailableLabel)}</td>`;
      case 'price':
        return `<td class="${cellClass}" data-colname="${colname}">${product.price_html || escapeHtml(unavailableLabel)}</td>`;
      case 'is_in_stock':
        return `<td class="${cellClass}" data-colname="${colname}">${escapeHtml(product.stock_label || unavailableLabel)}</td>`;
      case 'product_cat':
        return `<td class="${cellClass}" data-colname="${colname}">${escapeHtml(product.categories || unavailableLabel)}</td>`;
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
        return `<td class="${cellClass}" data-colname="${colname}"><p><span aria-hidden="true">${escapeHtml(unavailableLabel)}</span></p></td>`;
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
