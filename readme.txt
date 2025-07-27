=== Snappy Search ===
Contributors: polyplugins
Tags: instant search, search, wp, snappy search, woocommerce
Tested up to: 6.8
Stable tag: 1.2.0
Requires PHP: 7.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A fast, lightweight search plugin powered by TNTSearch, indexing posts for instant, accurate results.


== Description ==
Snappy Search is a powerful and lightweight AJAX search plugin that enhances your site's search functionality with lightning fast results. Powered by [TNTSearch](https://github.com/teamtnt/tntsearch), it indexes your WordPress posts for instant, accurate, and efficient searching. Say goodbye to slow searches, this plugin ensures a seamless user experience with improved relevancy. Perfect for blogs, news sites, and content heavy websites. For even faster search performance, install our [Snappy Search Enhancements](https://www.polyplugins.com/product/snappy-search-enhancements/) MU plugin to disable unnecessary plugins during search requests.


== Currently Supports ==

* Instantly searching WooCommerce products
* Instantly searching Easy Digital Downloads downloads
* Instantly searching WordPress Posts
* Instantly searching WordPress Pages
* Advanced repo search for finding plugins and themes. [Demo](https://www.polyplugins.com/repo-search/)


== Features ==

* Search through all posts fast without requiring multiple page loads
* Set how many characters to trigger the AJAX search
* Set a typing delay before an AJAX request is made
* Tab selection between various indexes if more than one is enabled
* Adds a dropdown by defined selector to search form to show results
* Shortcode [snappy_search_polyplugins] to inject a Snappy Search form anywhere
* Can build indexes for products, downloads, posts, and pages
* Background sync for indexes
* Index updater that handles when data is added, updated, removed, set to draft, or visibility hidden.
* Ability to adjust the batch size for the initial index so smaller servers don't get overloaded
* Limit the number of results displayed
* Reindexer button
* Adds /wp-json/snappy-search/v1/products endpoint to get array of post id's (Requires WooCommerce)
* Adds /wp-json/snappy-search/v1/downloads endpoint to get array of post id's (Requires EDD)
* Adds /wp-json/snappy-search/v1/posts endpoint to get array of post id's
* Adds /wp-json/snappy-search/v1/pages endpoint to get array of post id's


== Road Map: ==

* Add support for using the WP DB instead of SQLite since TNTSearch supports it
* Add analytics for 0 search items and other things
* Add scroll to load more results
* Add logging class from our other plugins
* Add another shortcode for replacing advanced search
* Add ability for admin side nav to jump to sub settings

== GDPR ==

We are not lawyers and always recommend doing your own compliance research into third party plugins, libraries, ect, as we've seen other plugins not be in compliance with these regulations.

This plugin uses the Bootstrap, BootStrap Icons, and SweetAlert2 3rd party libraries. These libraries are loaded locally to be compliant with data protection regulations. This plugin also uses TNTSearch.

This plugin collects and stores certain data on your server to ensure proper functionality. This includes:

* Storing plugin settings
* Remembering which notices have been dismissed


== Installation ==

1. Backup WordPress
1. Upload the plugin files to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Configure the plugin


== Frequently Asked Questions ==

= How long will it take to index? =

By default it will index 10 posts per minute.


== Screenshots ==

1. Demo
2. General Settings
3. Post Settings
4. Pages Settings
5. Products Settings
6. Repo Settings


== Changelog ==

= 1.1.0 =
* Added: Support for WooCommerce Products
* Added: Support for Easy Digital Downloads
* Added: Support for Pages
* Added: Shortcode [snappy_search_polyplugins] to inject a Snappy Search form anywhere
* Added: Index updater that handles when data is added, updated, removed, set to draft, or visibility hidden.
* Added: Reindex button to settings
* Added: Characters and Typing Delay options under General settings
* Added: Enabled option under various index types
* Added: Tab navigation to search if showing more than one index
* Updated: Name to Snappy Search
* Bugfix: Cron jobs may not register during activation

= 1.0.1 =
* Added: Repo Advanced Search

= 1.0.0 =
* Initial Release