=== Simple Object Cache Flusher ===
Contributors: mainulspace
Tags: cache, object cache, memcached, redis, flush, admin
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.2
Stable tag: 1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Easily flush Memcached, Redis, or any object cache from the WordPress admin. Detects cache backend and provides a simple admin UI to clear the cache.

== Description ==

A minimal admin tool for all WordPress sites using persistent object cache (Memcached, Redis, APC, etc.). Detects the backend and lets admins clear the cache with one click. Especially useful for users on shared hosting or without SSH access.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/simple-object-cache-flusher` directory, or install through the WordPress plugins screen.
2. Activate the plugin.
3. Go to Tools > Flush Object Cache.

== Frequently Asked Questions ==

= What caches does it support? =
It detects Memcached, Redis, APC, and most custom drop-in caches.

= Does it show cache stats? =
No, it only shows the backend type and offers a flush button for all admins.

== Screenshots ==
1. Flush Object Cache admin UI

== Changelog ==

= 1.1 =
* Initial release.

== Upgrade Notice ==

= 1.1 =
First public version.

== Support ==
Submit issues or improvements: https://github.com/mainulspace/wordpress/tree/master/plugins/simple-object-cache-flusher

== Credits ==
Mainul Hasan, https://www.webdevstory.com/