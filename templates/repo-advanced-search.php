
<div class="bootstrap-wrapper">
  <div class="row page_breadcrumb mayosis-global-breadcrumb-style">
    <div class="container">
      <h1 class="page_title_single">Repo Search</h1>
    </div>
  </div>

  <section class="container mx-auto py-4">
    <div class="row">
      <div class="col-12 col-md-3 filters">
        <div class="filter-heading mb-4">Sort By</div>
        <div class="sort-container mb-4">
          <select class="form-select" id="sort">
            <option value="relevance" selected>Relevance</option>
            <option value="most-installed">Most Installed</option>
            <option value="least-installed">Least Installed</option>
            <option value="top-rated">Top Rated</option>
            <option value="least-rated">Least Rated</option>
            <option value="newest">Newest</option>
            <option value="oldest">Oldest</option>
          </select>
        </div>
        
        <div class="repo-container">
          <select class="form-select" id="repo">
            <option value="plugins" selected>Plugins</option>
            <option value="themes">Themes</option>
          </select>
        </div>

        <div class="text-center mt-4">
          Powered by <a href="https://wordpress.org/plugins/speedy-search/" target="_blank">Speedy Search</a>
        </div>
      </div>
      <div class="col-12 col-md-9 search-container">

        <div class="search mb-4">
          <input type="text" class="form-control" id="search" placeholder="Search items...">
        </div>

        <div class="row results">
        </div>
      </div>
    </div>


  </section>
</div>