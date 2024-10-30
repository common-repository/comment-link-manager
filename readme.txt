=== Comment Link Manager ===
Contributors: rrolfe
Donate link: http://www.weberz.com/
Tags: comments, spam, nofollow, new window, author links, comment links
Requires at least: 2.8
Tested up to: 3.2.1
Stable tag: 1.1

CLM enables admins to disable author links, open links in new window, and remove the nofollow tag from links that are left in comments by visitors.

== Description ==

The CLM plugin allows wordpress admins to have more control over the links left by users who leave 
comments on their blogs.  Admins are provided with the ability to remove all author links from comments
unless the user has made an admin specified number of comments.  You are also provided with the ability 
to override that setting with whitelist and blacklist features.  The full list of features provided by 
the CLM plugin is below

**Features**

* Enables admins to remove nofollow attribute from comment author links
* Allows for optional removal of nofollow attribute from links left in the body of comments
* Provides admins with the ability to open comment author and body links in a new browser window.
* Provides ability for admins to require a specified number of comments before enabling comment author links
* Has built in whitelist and blacklist functions to override general setting of required number of comments

== Installation ==

Installation of the comment link manager plugin is very straight forward.

1. Upload `comment-link-manager.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress Admin
1. Use the `Settings -> Comment Link Manager` options page to enable desired features.

== Frequently Asked Questions ==

= Why are there no questions here? =

Simple.. Nobody has asked any yet!

== Screenshots ==

1. Admin panel options page for comment link manager plugin

== Changelog == 

= 1.1 = 2011-11-14

* Removed PHP Short tags
* Changed from print() to echo
* Fixed invalid use of _e function
* Generally cleaned up code

= 1.0 = 2009-06-18

* Initial Release

== Upgrade Notice ==

= 1.1 = 
Removes bad code which required PHP short tags to be turned on.

