const { __, _x, _n, _nx } = wp.i18n;
let selector = speedy_search_object.selector;

document.addEventListener("DOMContentLoaded", function () {
  if (!selector) return;
  
  const searchInput = document.querySelector(selector);
  const searchForm = searchInput?.closest("form");
  let is_searching = false;

  if (!searchInput || !searchForm) return;

  // Create and insert the results container dynamically
  let resultsContainer = document.createElement("div");
  resultsContainer.className = "instant-search-results";
  searchForm.insertAdjacentElement("afterend", resultsContainer);

  let typingTimer;
  const typingDelay = 300;

  searchInput.addEventListener("input", function () {
    clearTimeout(typingTimer);
    const query = searchInput.value.trim();

    if (query.length > 4) {
      typingTimer = setTimeout(() => performSearch(query), typingDelay);
    } else {
      resultsContainer.innerHTML = ""; // Clear results if less than 3 chars
    }
  });

  function performSearch(query) {
    if (is_searching) {
      return;
    }

    is_searching = true;

    resultsContainer.innerHTML = "<p>" + __('Searching...', 'speedy-search') + "</p>"; // Show loading state

    fetch(
      `/wp-json/speedy-search/v1/posts/?search=${encodeURIComponent(
        query
      )}`
    )
      .then((response) => response.json())
      .then((data) => {
        if (!data.length) {
          resultsContainer.innerHTML = "<p>" + __('No results found.', 'speedy-search') + "</p>";
          return;
        }

        resultsContainer.innerHTML = data
          .map(
            (post) => `
                <div class="instant-search-result">
                    <a href="${post.permalink}">
                        <img src="${post.thumbnail}" alt="${post.title}">
                        <h2>${post.title}</h2>
                        <p>${post.excerpt}</p>
                    </a>
                </div>
            `
          )
          .join("");
      })
      .catch((error) => {
        console.error("Search Error:", error);
        resultsContainer.innerHTML =
          "<p style='margin-bottom: 0;'>" + __('Error loading results.', 'speedy-search') + "</p>";
      });

    is_searching = false;
  }
});
