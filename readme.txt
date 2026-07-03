=== DDYS WordPress Plugin ===
Contributors: ddysiodev
Tags: ddys, movies, shortcode, block, video
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed DDYS API content with shortcodes, Gutenberg blocks, caching, and a configurable API base URL.

== Description ==

DDYS WordPress Plugin lets site owners embed DDYS API content into WordPress posts, pages, widgets, and blocks.

Features:

* Configurable API Base URL for the official API or your own Worker Proxy.
* Shortcodes for movies, latest, hot, search, calendar, movie detail, sources, collections, shares, requests, activities, users, and dictionaries.
* Lightweight Gutenberg blocks for common displays.
* WordPress Transients API caching.
* Cache management and diagnostics screens.
* API connection test.
* Responsive frontend styles.
* Optional authenticated request form, disabled by default.

Official website: https://ddys.io/

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/ddys-wordpress-plugin`.
2. Activate the plugin through the WordPress Plugins screen.
3. Open the DDYS menu in wp-admin.
4. Configure the API Base URL.
5. Add shortcodes or DDYS blocks to posts and pages.

== Frequently Asked Questions ==

= Does the plugin require an API key? =

No. Public display features use public read endpoints. Authenticated features are disabled by default.

= Can I use a Cloudflare Worker Proxy? =

Yes. Set API Base URL to your deployed Worker endpoint, such as `https://example.com/ddys-api`.

= Does it cache API responses? =

Yes. Public GET responses are cached with WordPress transients. You can flush cache from the DDYS Cache screen.

= Does it require npm or a build step? =

No. The plugin ships plain PHP, CSS, and JavaScript.

== Screenshots ==

1. DDYS settings screen.
2. Shortcode generator.
3. Cache management.
4. Frontend latest movies grid.

== Changelog ==

= 0.1.0 =
* Initial public release.

== Upgrade Notice ==

= 0.1.0 =
Initial release.
