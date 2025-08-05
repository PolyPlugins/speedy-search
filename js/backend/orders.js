const { __, _x, _n, _nx } = wp.i18n;

let selector          = '#orders-search-input-search-input';
let characters        = speedy_search_object.options?.characters ?? 4;
let typing_delay      = speedy_search_object.options?.typing_delay ?? 300;
let posts_enabled     = speedy_search_object.options?.posts?.enabled ?? false;
let pages_enabled     = speedy_search_object.options?.pages?.enabled ?? false;
let products_enabled  = speedy_search_object.options?.products?.enabled ?? false;
let downloads_enabled = speedy_search_object.options?.downloads?.enabled ?? false;
let currency          = speedy_search_object.currency ?? '$';

jQuery(document).ready(function ($) {
  if (!selector) return;

  const $orderList        = $('#the-list');
  const $searchInput      = $(selector);
  const typingDelay       = typing_delay;

  init();
  
  function init() {
    listener();
    close();
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

    fetchResults(query, 'orders');
  }

  function fetchResults(query, endpoint) {
    $.ajax({
      url: "/wp-json/speedy-search-search/v1/" + endpoint,
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
          data.forEach(function(order) {
            var row = `
              <tr id="order-${order.id}" class="order-${order.id} type-shop_order status-${order.order_status}">
                <th scope="row" class="check-column">
                  <input id="cb-select-${order.id}" type="checkbox" name="id[]" value="${order.id}">
                </th>
                <td class="order_number column-order_number has-row-actions column-primary" data-colname="Order">
                  <a href="#" class="order-preview" data-order-id="${order.id}" title="Preview">Preview</a>
                  <a href="/wp-admin/post.php?post=${order.id}&action=edit" class="order-view">
                    <strong>#${order.order_number} ${order.billing_first_name} ${order.billing_last_name}</strong>
                  </a>
                  <div class="order_date small-screen-only">
                    <time datetime="${order.order_date}">${formatDate(order.order_date)}</time>
                  </div>
                  <div class="order_status small-screen-only">
                    <mark class="order-status status-${order.order_status} tips"><span>${toTitleCase(order.order_status.replace(/-/g, ' '))}</span></mark>
                  </div>
                  <button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
                </td>
                <td class="order_date column-order_date" data-colname="Date">
                  <time datetime="${order.order_date}">${formatDate(order.order_date)}</time>
                </td>
                <td class="order_status column-order_status" data-colname="Status">
                  <mark class="order-status status-${order.order_status} tips"><span>${toTitleCase(order.order_status.replace(/-/g, ' '))}</span></mark>
                </td>
                <td class="billing_address column-billing_address hidden" data-colname="Billing">
                  ${order.billing_first_name} ${order.billing_last_name}, ${order.billing_address_1}, ${order.billing_city}<span class="description"></span>
                </td>
                <td class="shipping_address column-shipping_address hidden" data-colname="Ship to">
                  ${order.shipping_first_name} ${order.shipping_last_name}, ${order.shipping_address_1}, ${order.shipping_city}<span class="description"></span>
                </td>
                <td class="order_total column-order_total" data-colname="Total">
                  <span class="tips">
                    <span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">$</span>${order.total}</span>
                  </span>
                </td>
                <td class="wc_actions column-wc_actions hidden" data-colname="Actions">
                  <p>
                    <a class="button wc-action-button wc-action-button-processing processing" href="#" aria-label="Processing">Processing</a>
                    <a class="button wc-action-button wc-action-button-complete complete" href="#" aria-label="Complete">Complete</a>
                  </p>
                </td>
                <td class="origin column-origin" data-colname="Origin">
                  <span class="origin-text">${formatOrigin(order.origin) || '—'}</span>
                </td>
              </tr>
            `;
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
    return raw
      .replace(/[()]/g, '')                  // remove parentheses
      .split(/\s+/)                          // split by space
      .map(word => word.charAt(0).toUpperCase() + word.slice(1)) // capitalize each
      .join(' ');
  }

});