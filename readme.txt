=== Speedy Search ===
Contributors: polyplugins
Tags: instant search, search, wp, speedy search, woocommerce
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A fast, lightweight search plugin powered by TNTSearch, indexing posts for instant, accurate results.


== Description ==
Speedy Search is a powerful and lightweight search plugin that enhances your site’s search functionality with lightning-fast results. Powered by [TNTSearch](https://github.com/teamtnt/tntsearch), it indexes your WordPress posts for instant, accurate, and efficient searching. Say goodbye to slow searches—this plugin ensures a seamless user experience with real-time suggestions and improved relevancy. Perfect for blogs, news sites, and content-heavy websites.


== Currently Supports ==

* Instantly Searching WordPress Posts


== Features ==

* Adds a dropdown by defined selector to search form to show results
* Search through all posts fast without requiring multiple page loads
* Builds an index of all posts
* Background sync to index posts
* Ability to adjust the batch size for the initial index so smaller servers don't get overloaded
* Limit the number of results displayed
* Adds /wp-json/speedy-search/v1/posts endpoint to get array of post id's


== Road Map: ==

* Add support for WooCommerce
* Add support for pages
* Add integration with Admin Instant Search

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

1. General Settings
2. Post Settings

