jQuery(document).ready(function ($) {

  let is_searching = false;

  let typingTimer;
  const typingDelay = 300;

  $(".search input").on("input", function () {
    clearTimeout(typingTimer);
    const query = $(this).val();

    if (query.length > 0) {
      typingTimer = setTimeout(() => performSearch(query), typingDelay);
    }
  });

  $(".filters #sort").on("change", function () {
    let query = $(".search input").val();
    
    if (query) {
      performSearch(query);
    }
  });

  $(".filters #repo").on("change", function () {
    let query = $(".search input").val();
    
    if (query) {
      performSearch(query);
    }
  });

  function performSearch(query) {
    if (is_searching) return;

    is_searching = true;

    $('.results .card').addClass('loading');

    let sort = $('#sort').val();
    let repo = $('#repo').val();

    if (!sort) {
      sort = 'relevance';
    }

    if (!repo) {
      repo = 'plugins';
    }

    $.ajax({
      url: `https://www.polyplugins.com/wp-json/repo-search/v1/` + repo + `/?search=${encodeURIComponent(query)}&sort=${encodeURIComponent(sort)}`,
      method: "GET",
      dataType: "json",
    })
      .done(function (data) {
        const $container = $('.row .results');
        $container.empty();
        data.forEach(item => {
          let image = repo === 'plugins' ? item.icon_1x : item.screenshot_url;

          const cardHtml = `
            <div class="col-12 col-md-4 mb-4">
              <a href="https://wordpress.org/${repo}/${item.slug}" class="card-url" target="_blank">
                <div class="card h-100">
                  <div class="repo-image-container mt-4 text-center">
                    <img src="${image}" class="repo-image" alt="Item image">
                  </div>
                  <div class="card-body pb-0 text-center d-flex justify-content-center align-items-center" style="height: 100%;">
                    <div class="card-title mb-0">${item.name}</div>
                  </div>
                  <div class="mt-auto p-4 pt-0 text-center">
                    <div class="rating">
                      ${generateStars(item.rating)}
                    </div>
                    <p class="card-text small text-muted">${Number(item.active_installs).toLocaleString()} Installs</p>
                  </div>
                </div>
              </a>
            </div>
          `;
          $container.append(cardHtml);
        })
      })
      .fail(function (jqXHR, textStatus, errorThrown) {
        alert("Error loading results");
      })
      .always(function () {
        is_searching = false;
        $('.results .card').removeClass('loading');
      });
  }

  function generateStars(rating) {
    let stars = Math.round(rating / 20 * 2) / 2; // rounds to nearest 0.5
    let fullStars = Math.floor(stars);
    let halfStar = (stars - fullStars) === 0.5 ? 1 : 0;
    let emptyStars = 5 - fullStars - halfStar;

    let starsHtml = '';
    for (let i = 0; i < fullStars; i++) {
      starsHtml += '<i class="bi bi-star-fill"></i>';
    }
    if (halfStar) {
      starsHtml += '<i class="bi bi-star-half"></i>';
    }
    for (let i = 0; i < emptyStars; i++) {
      starsHtml += '<i class="bi bi-star"></i>';
    }
    return starsHtml;
  }

});
