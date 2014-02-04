=== CyberSyn ===
Contributors: CyberSEO
Plugin URI: http://www.cyberseo.net/cybersyn/
Author: CyberSEO
Author URI: http://www.cyberseo.net/
Tags: RSS, Atom, Feed, Feeds, Content, Syndicator, Syndication, Aggregator, Aggregation, Parser, Autoblog, Autoblogging, Content Curation, Spinner, WordAi, TBS, The Best Spinner, YouTube, YouTube Video Parser
Requires at least: 3.0.0
Tested up to: 3.8.1
Stable tag: 4.3

A powerful, lightweight and easy to use Atom/RSS syndicating plugin for WordPress.

== Description ==

The CyberSyn plugin (developed by [CyberSEO.net](http://www.cyberseo.net/ "CyberSEO.net")) is powerful, lightweight and easy to use Atom/RSS syndicating plugin for WordPress.

Features:

1. Parses the RSS and Atom feeds for specific XML elements only and ignores everything else. So it's rather compact and extremely fast.
2. Integrated with popular TBS (The Best Spinner) and WordAi content spinners.
3. Allows to automatically embed videos from standard YouTube RSS feeds. Just enable the "Embed videos" option and plugin will automatically extract and embed YouTube videos among with their full descriptions directly into your posts.
4. Allows to store syndicated images on the local host.
5. Advanced RSS/Atom parsing algorithm has the ability to pull the feeds fully automatically. Furthermore, you can assign the updating period to each particular feed. Also you can set up a maximum number of posts that will be syndicated at once. This is a very useful feature for SEO of your blogs.
6. Adjustable post uniqueness identification by GUID, post name or both.
7. The plugin has no problem with syndicating various embedded media content such as streaming videos, flash objects etc.

Requirements:


1. PHP 5.2.4 or greater
2. MySQL 5.0 or greater
3. PHP mbstring extension
4. PHP cURL extension (recommended)
5. PHP variable safe_mode must be disabled (if cURL is not installed)
6. PHP variable allow_url_fopen must be enabled (if cURL is not installed)
7. Access to cron on server (recommended)


== Installation ==

1. Upload 'cybersyn' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Where can I get support? =

The support forums can be found at http://www.cyberseo.net/forum/.

== Screenshots ==

1. Syndicating a new RSS feed.

== Changelog ==

= 3.21 =
* Fixed bug that caused "Fatal error: Call to undefined function"
* Fully compatible with WordPress 3.8.1

= 3.20 =
* The plugin is now integrated with WordAi spinner that uses artificial intelligence to understand text and is able to automatically rewrite the syndicated articles with the same readability as a human writer.

= 3.13 =
* Fully compatible with WordPress 3.6.

= 3.12 =
* The default user agent header has been removed from HTTP requests. It was causing problems with FeedBurner feeds.

= 3.11 =
* The Best Spinner integration has been improved.

= 3.10 =
* Added possibility to automatically embed videos from standard YouTube RSS feeds.

= 3.02 =
* Fixed bug which forced the plugin to use "The Best Spinner" even if disabled.

= 3.01 =
* Minor changes.

= 3.00 =
* The plugin is now integrated with TBS (The Best Spinner) - the most popular content spinning service.
* The post images now can be stored locally (copied to your own host).
* The syndicated posts now can be attributed to the chosen author.
* Now one can specify a list of tags for for the each feed.
* The media attachment handling has been sufficiently improved.
* The character encoding conversion has been added.
* Now one can specify the HTML code with will be inserted to the bottom of each syndicated post (so-called post footers).

= 2.11 =
* The "[loss of permalink](http://www.cyberseo.net/forum/support-eng/loss-of-permalink/)" issue fixed.

= 2.10 =
* The "Link syndicated posts to the original source" option has been added.
* The RSS auto pull mode now uses the built-in WP pseudo cron.
* Minor bugs fixed.

= 2.1 =
* All known bugs were fixed.

= 2.0 =
* The CyberSyn plugin is now 100% compatible with WordPress 3.3.
* The UI has been improved.
* Default settings have been removed from the "XML Syndicator" page. Use the "Alter default settings" button instead.

= 1.4 =
* The feed import function has been improved. Now the status of 'safe_mode' and 'allow_url_fopen' PHP variables is not important in case if the PHP cURL extension is installed.
* The user interface has been slightly improved.

= 1.3 =
* "RSS Pull Mode" option switching issue has been fixed.

= 1.2 =
* First public GPL release.

== Upgrade Notice ==

Upgrade using the automatic upgrade in Wordpress Admin.