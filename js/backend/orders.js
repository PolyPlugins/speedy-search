const { __, _x, _n, _nx } = wp.i18n;

let selector          = '#orders-search-input-search-input, #post-search-input';
let characters        = speedy_search_object.options?.characters ?? 4;
let typing_delay      = speedy_search_object.options?.typing_delay ?? 300;
let posts_enabled     = speedy_search_object.options?.posts?.enabled ?? false;
let pages_enabled     = speedy_search_object.options?.pages?.enabled ?? false;
let products_enabled  = speedy_search_object.options?.products?.enabled ?? false;
let downloads_enabled = speedy_search_object.options?.downloads?.enabled ?? false;
let currency          = speedy_search_object.currency ?? '$';
let orders_endpoint   = speedy_search_object.endpoints?.orders ?? "/wp-json/speedy-search-search/v1/orders";
let unavailableLabel  = 'Error';

jQuery(document).ready(function ($) {
  if (!selector) return;

  const $orderList        = $('#the-list');
  const $searchInput      = $(selector);
  const typingDelay       = typing_delay;

  init();
  
  function init() {
    listener();
  }

  function listener() {
    let typingTimer;

    if (!$searchInput.length) return;

    $searchInput.on("input", function () {
      clearTimeout(typingTimer);
      const query = $.trim($searchInput.val());

      if (query.length >= characters) {
        typingTimer = setTimeout(function () {
          performSearch(query);
        }, typingDelay);
      } else {
        // Could show a message like: "Keep typing..."
      }
    });
  }

  function performSearch(query) {
    if ($orderList.length) {
      $orderList.addClass('skeleton-loading');

      // Can't skelton load the origin without putting the text in another element
      $('#the-list.skeleton-loading .origin').each(function() {
        var $el = $(this);
        var text = $el.contents().filter(function() {
          return this.nodeType === 3 && this.nodeValue.trim() !== '';
        });

        if (text.length) {
          text.wrap('<span class="origin-text"></span>');
        }
      });
    }

    fetchResults(query);
  }

  function fetchResults(query) {
    $.ajax({
      url: orders_endpoint,
      data: { search: query },
      dataType: "json",
      beforeSend: function (xhr) {
        xhr.setRequestHeader('X-WP-Nonce', speedy_search_object.nonce);
      },
      success: function (data) {
        if (!data.length) {
          $orderList.removeClass('skeleton-loading');

          Swal.fire({
            title: "Error!",
            text: "No results found!",
            icon: "error"
          });
        } else {
          $orderList.empty(); // Clear existing rows
          $orderList.removeClass('skeleton-loading');
          const columns = getOrderColumns();

          data.forEach(function(order) {
            const row = renderOrderRow(order, columns);
            $orderList.append(row);
          });

        }
      },
      error: function (xhr, status, error) {
        Swal.fire({
          title: "Error!",
          text: "A server error occurred, reverted to original orders.",
          icon: "error"
        });

        $orderList.removeClass('skeleton-loading');
      }
    })
  }

  function formatDate(dateStr) {
    var d = new Date(dateStr);

    return d.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    });
  }

  function toTitleCase(str) {
    return str.replace(/\w\S*/g, function(txt) {
      return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
    });
  }

  function formatOrigin(raw) {
    if (!raw) {
      return '';
    }

    return raw
      .replace(/[()]/g, '')                  // remove parentheses
      .split(/\s+/)                          // split by space
      .map(word => word.charAt(0).toUpperCase() + word.slice(1)) // capitalize each
      .join(' ');
  }

  function getOrderColumns() {
    const columns = [];

    $('table.wp-list-table thead tr:first-child').find('th, td').each(function () {
      const $cell = $(this);
      const className = $cell.attr('class') || '';
      const classes = className.split(/\s+/);
      const columnClass = classes.find(function(c) {
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

  function renderOrderRow(order, columns) {
    const orderId = escapeHtml(order.id ?? '');
    const rowClass = escapeHtml(`iedit post-${order.id} type-shop_order status-${order.order_status}`);
    let row = `<tr id="post-${orderId}" class="${rowClass}">`;

    columns.forEach(function(column) {
      if (column.type === 'check') {
        row += `
          <th scope="row" class="check-column">
            <input id="cb-select-${orderId}" type="checkbox" name="post[]" value="${orderId}">
          </th>
        `;
        return;
      }

      row += renderOrderCell(column, order);
    });

    row += '</tr>';

    return row;
  }

  function renderOrderCell(column, order) {
    const hiddenClass = column.hidden ? ' hidden' : '';
    const cellClass = `${column.key} column-${column.key}${hiddenClass}`;
    const colname = escapeHtml(column.label || column.key);
    const fullName = `${order.billing_first_name || ''} ${order.billing_last_name || ''}`.trim();
    const escapedName = escapeHtml(fullName || unavailableLabel);
    const statusRaw = (order.order_status || '').replace(/-/g, ' ');
    const statusLabel = escapeHtml(toTitleCase(statusRaw || 'unknown'));
    const statusClass = escapeHtml(order.order_status || 'unknown');
    const orderDate = order.order_date ? formatDate(order.order_date) : unavailableLabel;

    switch (column.key) {
      case 'order_number':
        return `
          <td class="${cellClass} has-row-actions column-primary" data-colname="${colname}">
            <a href="#" class="order-preview" data-order-id="${escapeHtml(order.id)}" title="Preview">Preview</a>
            <a href="/wp-admin/post.php?post=${escapeHtml(order.id)}&action=edit" class="order-view">
              <strong>#${escapeHtml(order.order_number || order.id)} ${escapedName}</strong>
            </a>
            <div class="order_date small-screen-only">
              <time datetime="${escapeHtml(order.order_date || '')}">${escapeHtml(orderDate)}</time>
            </div>
            <div class="order_status small-screen-only">
              <mark class="order-status status-${statusClass} tips"><span>${statusLabel}</span></mark>
            </div>
            <button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
          </td>
        `;
      case 'order_date':
        return `
          <td class="${cellClass}" data-colname="${colname}">
            <time datetime="${escapeHtml(order.order_date || '')}">${escapeHtml(orderDate)}</time>
          </td>
        `;
      case 'order_status':
        return `
          <td class="${cellClass}" data-colname="${colname}">
            <mark class="order-status status-${statusClass} tips"><span>${statusLabel}</span></mark>
          </td>
        `;
      case 'billing_address':
        return `<td class="${cellClass}" data-colname="${colname}">${escapeHtml(buildAddress(order, 'billing'))}</td>`;
      case 'shipping_address':
        return `<td class="${cellClass}" data-colname="${colname}">${escapeHtml(buildAddress(order, 'shipping'))}</td>`;
      case 'order_total':
        return `
          <td class="${cellClass}" data-colname="${colname}">
            <span class="tips">
              <span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">${escapeHtml(currency)}</span>${escapeHtml(order.total || '0.00')}</span>
            </span>
          </td>
        `;
      case 'wc_actions':
        return `<td class="${cellClass}" data-colname="${colname}"><p><span aria-hidden="true">${escapeHtml(unavailableLabel)}</span></p></td>`;
      case 'origin':
        return `<td class="${cellClass}" data-colname="${colname}"><span class="origin-text">${escapeHtml(formatOrigin(order.origin) || unavailableLabel)}</span></td>`;
      default:
        return `<td class="${cellClass}" data-colname="${colname}"><span aria-hidden="true">${escapeHtml(unavailableLabel)}</span></td>`;
    }
  }

  function buildAddress(order, type) {
    const firstName = order[`${type}_first_name`] || '';
    const lastName = order[`${type}_last_name`] || '';
    const address1 = order[`${type}_address_1`] || '';
    const city = order[`${type}_city`] || '';
    const combined = [firstName, lastName].filter(Boolean).join(' ').trim();
    const parts = [combined, address1, city].filter(Boolean);

    return parts.length ? parts.join(', ') : unavailableLabel;
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