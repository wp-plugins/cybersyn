=== CyberSyn ===
Contributors: CyberSEO
Plugin URI: http://www.cyberseo.net/cybersyn/
Author: CyberSEO
Author URI: http://www.cyberseo.net/
Tags: RSS, Atom, Feed, Feeds, Content, Syndicator, Syndication, Aggregator, Aggregation, Parser, Autoblog, Autoblogging
Requires at least: 2.0.0
Tested up to: 3.3.1
Stable tag: 4.3

A powerful, lightweight and easy to use Atom/RSS syndicating plugin for WordPress.

== Description ==

The CyberSyn plugin (developed by [CyberSEO.net](http://www.cyberseo.net/ "CyberSEO.net")) is powerful, lightweight and easy to use Atom/RSS syndicating plugin for WordPress.

Features:

1. The CyberSyn plugin parses the RSS feeds for those XML elements only that can be imported into WordPress posts and ignores everything else. So it has a rather compact and extremely fast code!
2. Advanced RSS parsing algorithm has an ability to pull the feeds fully automatically. Furthermore, you can assign the updating period to each particular feed. Also you can set up a maximum number of posts that will be syndicated from the feed at once. This is a very useful feature for SEO of your blogs, because search engines don't like blogs that add 100 or more posts at once.
3. Adjustable post uniqueness identification by GUID, post name or both.
4. CyberSyn has no problem with syndicating various embedded media content such as streaming videos, flash objects etc.

Requirements:

1. PHP 5.2.4 or greater
2. MySQL 5.0 or greater
3. PHP cURL extension (recommended)
4. PHP variable safe_mode must be disabled (if cURL is not installed)
5. PHP variable allow_url_fopen must be enabled (if cURL is not installed)
6. Access to cron on server (recommended)

== Installation ==

1. Upload 'cybersyn' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Where can I get support? =

The support forums can be found at http://www.cyberseo.net/forum/.

== Screenshots ==

1. Syndicating a new RSS feed.

== Changelog ==

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