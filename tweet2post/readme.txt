=== Tweet2Post ===
Contributors: vortexbased
Donate link: http://www.tweet2post.com
Tags: twitter, tweets, news, feed, import, posts
Requires at least: 3.8
Tested up to: 4.5.1
Stable tag: 1.1.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Tweet2Post searches for tweets with specific hashtags in Twitter and imports/saves them as WordPress posts.

== Description ==

<blockquote><p><strong><a href="http://www.tweet2post.com/feature-requests" title="Request a new feature to be added to Tweet2Post">Feature Requests</a> are welcome and <a href="http://www.tweet2post.com/support" title="Get help using Tweet2Post">Support</a> is free!</strong></p></blockquote>

= Overview =
Tweet2Post is a WordPress plugin that allows you to import tweets as posts.

* <strong> A list of hashtags gives control over which tweets are imported.</strong><br />Options include setting a "master hashtag" to import all tweets using that hashtag.
 
* <strong>You can link hashtags to post categories and define a default category for post without a category hashtag.</strong><br>Define as many hashtags for category (or categories for a hashtag) as you like. All tweets will be posted accordingly.

* <strong>You have the option to define a Custom Post Type (and custom categories) for imported tweets.</strong>

* <strong>All posts will use SEFs.</strong><br> A santized version of the tweeted text is used as the Search Engine Friendly URL (slug). This will make it easier for Google to rank the newly created pages.

* <strong>The included Scheduler can be used to import tweets automatically.</strong> The automatic import can be scheduled to run every 1/5/15/30 minutes, as well as hourly and daily. You can also run a manual import from the plugin settings page.

= Why use Tweet2Post =

* A simple way to archive, categorize, and display imported tweets as post on your website.
* Easily update your website with content relevant to your site visitors and their interests.
* Your tweets flow naturally with the rest of your website content.
* Search engines love sites that are updated regularly - depending on the import frequency, this could have a positive effect on your site's Search Engine rankings.

== Installation ==

Manual Installation
1. Upload the 'tweet2post' directory to the '/wp-content/plugins/' directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to 'Settings > Tweet2Post' and configure the plugin

Automatic Installation
1. Login to your WordPress Site
1. Click on Plugins => Add New
1. Search for "Tweet2Post" and click on install

= Requirements =
* PHP5 with cURL extension
* A free [Twitter App][twitter app]

[twitter app]: https://apps.twitter.com/
	"Setup your own FREE Twitter App"

== Frequently Asked Questions ==

= What is a Twitter App, why do I need it, and how do I get it? =
* A *Twitter App* is a free tool to communicate with the *Twitter API*.
* Tweet2Post needs to use a *Twitter App* to search for tweets.
* [Click Here][click here] to setup your *Twitter App*.

[click here]: https://apps.twitter.com/
	"Setup your own FREE Twitter App"

= Does Tweet2Post import tweets from all twitter accounts? =
Tweet2Post only imports from twitter accounts that have been added to WP user profiles.

= Does Tweet2Post work for all WP Users that added a twitter accounts to their profile? =
WP Users also need to be in a User Role selected in the plugin settings.

= Who will be the post author of an imported tweet? =
The WP User associated with the respective twitter account.

== Screenshots == 

1. Settings screen

== Changelog == 

= 1.1.5 = 
* Updated readme.txt

= 1.1.4 = 
* Updated readme.txt

= 1.1.3 = 
* Updated readme.txt

= 1.1.2 = 
* Updated readme.txt
* Added post-update notification
* Added anonymous usage statistics
* NEW Option to save hashtags as post tags
* NEW Option to remove hashtags from title completely
* NEW Option to remove hashtags from content completely

= 1.1.1 = 
* Updated readme.txt
* Removed screenshot from plugin archive

= 1.1 = 
* Updated readme.txt
* Fixed incorrect timing of scheduled imports when server time and local time are different
* Modified the way the post slug gets formated
* Added Widget to display latest Tweets
* NEW Option to remove # from hashtags for the title
* NEW Option to remove # from hashtags for the content

= 1.0 = 
* Initial release of the Tweet2Post plugin

== Upgrade Notice == 

= 1.1.4 =
Overwrite old plugin files with new ones.

= 1.0 =
Initial release of Tweet2Post plugin

== Notes ==

This plugin is based on the defunct 'Tweets As Posts' plugin by *Chandesh Parekh*.

[Feature Requests][feature Requests] are welcome and [Support][support] is free!

[feature Requests]: http://www.tweet2post.com/feature-requests
	"Request a new feature to be added to Tweet2Post"

[support]: http://www.tweet2post.com/support
	"Get help using Tweet2Post"
