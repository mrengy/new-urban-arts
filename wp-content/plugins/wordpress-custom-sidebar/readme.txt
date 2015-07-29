=== WordPress Custom Sidebar ===
Contributors: destio
Donate link: http://www.destio.de/tools/wordpress-custom-sidebar/
Tags: custom sidebar, dropdown, dynamic sidebar, sidebar, sidebar plugin, sidebar widget
Requires at least: 3.0+
Tested up to: 3.4.1
Stable tag: 2.2

With this plugin you can handle sidebar contents like posts and assign them from a dropdown menu.

== Description ==

With this plugin you can edit sidebar contents like posts in the admin area and assign them from a dropdown menu to any post or page. It supports flexible dropping via widget for independent theme association.

It's a matter of fact that people don't want to code when they just want to change their contents. To keep a website interesting for visitors you can change the sidebar contents in context to the main content. Before you could realize it with different sidebar templates or conditional tags. 
Now you can do all these great things inside the admin area without any coding.
Any anyway: Since we organize informations in databases all contents should be stored there as well.

From a dropdown menu you can easily choose all sidebars you have created.

= Features =

* Own `meta box` on post/page edit pages
* Own menu item in admin menu
* Registrates own `post type` for sidebars
* Full editable sidebars like posts
* Assign sidebars from dropdown menu
* Flexible widget support
* No coding needed
* Oldest sidebar as default post
* Loads sidebar from parent page if not assigned for children
* Localization in English and German
* Multiple widget support

= Related Links =

* [Homepage](http://www.destio.de/tools/wp-custom-sidebar/ "Homepage of WordPress Custom Sidebar")
* [Changelog](http://wordpress.org/extend/plugins/wordpress-custom-sidebar/changelog/ "Changelog for WordPress Custom Sidebar")
* [Questions](http://wordpress.org/extend/plugins/wordpress-custom-sidebar/faq/ "FAQ for WordPress Custom Sidebar")

**Note:**
First drop the widget in the sidebar of your choice. Now uses oldest sidebar as default sidebar. Before installing or upgrading new plugins, please backup your database first! After upgrading to 'WordPress Custom Sidebar 2.0' you have to assign the widget again. The plugin now supports multiple widgets.

== Installation ==

There are several ways to install a plugin in WordPress. For newbies this way is highly recommended:

1. Navigate to 'Add New' under 'Plugins' menu in admin area
1. Upload or choose `wordpress-custom-sidebar.zip` from 'Install Plugins'
1. Activate the plugin through the 'Plugins' menu in WordPress

Make sure that your theme is supporting widgets. Then add the 'Custom Sidebar' widget in any registered sidebar of your theme. This options are available under 'Widgets' in 'Appearance' menu. For more information look at [Widgetizing Themes - WordPress Codex](http://codex.wordpress.org/Widgetizing_Themes "Widgetizing Themes - WordPress Codex")

For more information please follow [WordPress Codex - Installing Plugins](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins "WordPress Codex - Installing Plugins").

== Frequently Asked Questions ==

= Do I have to add any code in the template? =

No, the only thing is to add a widget to the sidebar of your choice.

= How I can be sure that the plugin works properly? =

Create some more 'Custom Sidebars' and load one after another.

= Will this plugin work together with other plugins? =

Yes, the plugin is isolated in an own PHP class.

= Why this plugin needs multiple widget support? =

Some themes have different sidebar templates for posts/pages.

== Screenshots ==

1. screenshot-1.gif
1. screenshot-2.gif
1. screenshot-3.gif

== Changelog ==

= 2.2 =
* fixing some trouble in custom_sidebar_addcolumn()

= 2.1 =
* widget theme style support

= 2.0 =
* Localization, multiple widget support, functional updates

= 1.9 =
* Update of two functions, third screenshot

= 1.8 =
* Optimized code an better description

= 1.7 =
* Adding default sidebar entry with dummy text

= 1.6 =
* Optimized code and 'Create new sidebar' link

= 1.5 =
* `meta box` with dropdown menu for sidebar assignment

= 1.4 =
* `register_sidebar_widget` replaced with `wp_register_sidebar_widget`

= 1.3 =
* Adding widget support

= 1.2 =
* Optimized code and icon for sidebar `post type`

= 1.1 =
* Register own `post type` for sidebars

= 1.0 =
* Basic functionality with `conditional tags`

== Upgrade Notice ==

**Before installing or upgrading new plugins, please backup your database first.**