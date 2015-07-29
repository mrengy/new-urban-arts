=== Plugin Name ===
Contributors: MartyThornley, mindshare
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=11225299
Tags: php, browser detection, browser, internet explorer, iphone, mobile
Version: 2.1.2
Tested up to: 3.3.1
Stable tag: trunk

PHP Browser Detection is a WordPress plugin used to detect a user's browser.

== Description ==

PHP Browser Detection is a WordPress plugin used to detect a user's browser. 
It could be used to send conditional CSS files for Internet Explorer, display different content or custom messages anywhere on the page, or to swap out flash for an image for iPhones.

**Using the Template Tags in your theme:**

*Test for specific browsers:*

$version is optional. Include a major version number, a single integer - 3,4,5, etc... Or leave it empty to test for any version.

`<?php if ( is_firefox($version) ) { do stuff }; ?>`

`<?php if ( is_safari($version) ) { do stuff }; ?>`

`<?php if ( is_firefox($version) ) { do stuff }; ?>`

`<?php if ( is_chrome($version) ) { do stuff }; ?>`

`<?php if ( is_opera($version) ) { do stuff }; ?>`

`<?php if ( is_IE($version) ) { do stuff }; ?>`

Check for mobile, iPhone, iPad, iPod, etc...

`<?php if ( is_iphone($version) ) { do stuff }; ?>`

`<?php if ( is_ipad($version) ) { do stuff }; ?>`

`<?php if ( is_ipod($version) ) { do stuff }; ?>`

`<?php if ( is_mobile() ) { do stuff }; ?>`

Check specific versions...

`<?php if ( is_IE6() ) { do stuff }; ?>`

`<?php if ( is_IE7() ) { do stuff }; ?>`

`<?php if ( is_lt_IE6() ) { do stuff }; ?>`

`<?php if ( is_lt_IE7() ) { do stuff }; ?>`

`<?php if ( is_lt_IE8() ) { do stuff }; ?>`

**Or you can get all the info and do what you want with it:**

Get just the name...

`<?php $browserName = get_browser_name (); ?>`

Get the full version number - 3.2, 5.0, etc...

`<?php $browserVersion = get_browser_version (); ?>`

Or get it all in array...

`<?php $browserInfo = php_browser_info (); ?>`

== Installation ==

1. Automatically install through the Plugin Browser or...
1. Upload entire `php-browser-detection` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.

Or with MU / MultiSite:

1. Add 'php-browser-detection.php' and 'php_browser_detection_browscap.ini' to mu-plugins to make sure every blog has it auto activated.

== Changelog ==

= 2.1.2 =

* updated php_browser_detection_browscap.ini to version 5004

= 2.1.1 =

* updated php_browser_detection_browscap.ini to version 4911

= 2.1 =

* fixed path info to work with 'mu-plugins' folder, version 2.0 didn't know how to find it.
* better recognition of iPad and iPhone with iOS 4

= 2.0 =

* Added tests for iPad, iPod, Chrome, Opera
* Added ability to test for any version for each browser
* Added ability to get browser name and get browser version

= 1.2 =

* fixed the lesser than statements.
* They had been looking for lesser than or equal to
* Fixed the is_safari() statement.

= 1.1 =
* Fixed error on line 156 preventing activation

