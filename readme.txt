=== Plugin Name ===
Contributors: chsxf
Tags: thumbnails, magnify
Requires at least: 2.0
Tested up to: 2.6.2
Stable tag: 0.5

Detects automatically thumbnails without links in your posts and enables your visitors to magnify them to their full scale version.

== Description ==

mAutoPopup for WordPress is a plugin that allows you to display full scale images directly just by inserting their related thumbnails without any link into your posts or pages. As of its 2.x releases, WordPress enables users to display full scale images by inserting a link to the related file or to the dynamically generated page. mAutoPopup gets further by enabling you to display thoses images on the same page in a "popup" layer without the hassle of having to insert a link.

= Key features =

* Displays full scale images related to thumbnails found into your posts or pages in a "popup" layer without loading a new page.
* Allows full customization of the "popup" layer through a dedicated style sheet.
* Automatic detection of "maximizable" thumbnails.
* Fully integrated with the WordPress internationalization API.
* Rich Text Editor plugin

= Fixes and Improvements for version 0.5 =

* Added lightbox compatibility mode - Thumbnails identified by mAutoPopup can now be magnified using lightbox (require installed lightbox compatible plugin)
* Improved page scrolling tracking - Introducing smooth move option

= Upcoming additions and improvements =

* Dual style sheet editor with Simple and Advanced forms.

= System requirements =

* PHP version **4.3.0** or higher
* Javascript must be enabled on the target web browser

This plugin is my first contribution to the WordPress community and I hope you'll find it useful.

Christophe SAUVEUR

== Installation ==

1. Unzip this package in an empty directory.
1. Using you favorite FTP client, upload those files to your plugins directory onto your server.
It should be : /your-wordpress-root/wp-content/plugins/
1. Go to your Administration panel, in the Plugins section, and activate mAutoPopup.
1. Go to your Settings > mAutoPopup page and click Setup mAutoPopup to configure mAutoPopup using the default options.
1. *You're done !*

= Upgrading from any previous version =

Follow these steps if you are not using the automatic update tool provided by WordPress 2.5+

1. Go to your Administration panel, in the Plugins section, and deactivate mAutoPopup.
1. Replace all mAutoPopup files by those of your installation package.
1. Go to your Administration panel, in the Plugins section, and activate mAutoPopup.
1. Go to the Settings section, in the mAutoPopup sub-menu, and click Upgrade mAutoPopup.
1. *You're done !*

== Usage ==

Once activated, mAutoPopup works automatically for any post or page of you weblog. Insert a thumbnail from the Upload Manager of the post editor without any link to the file or the page and mAutoPopup will automatically detect it has a suitable thumbnail for magnification. A thumbnail surrounded any link tag won't be detected as suitable.

mAutoPopup works by default for all the pages or posts of you weblog.
However, its behavoir can be selected amongst four options :

* Active for all posts and pages (default)
* Active for all posts but those where the disabling quicktag is present
* Disabled for all posts but those where the enabling quicktag is present 
* Disabled

As mentioned, you can define a specific behavoir for some posts by inserting quicktags.
The disabling quicktag is <!--mautopopup:disable--> and the enabling quicktag is <!--mautopopup:enable-->.
If the correct behavoir is selected, the presence of one of these quicktags anywhere in a post will alter it.
You can type it directly into the code editor or, better, use the Rich Text Editor plugin provided along with mAutoPopup from version 0.4. 

= Rich Text Editor (TinyMCE) plugin usage =

mAutoPopup 0.4 includes a new rich text editor plugin to enable or disable mAutoPopup or to remove mAutoPopup quicktags from posts and pages.
Once installed, this plugin shows 3 buttons in the first toolbar of the rich text editor.

* **Enable button**
Adds the enabling quicktag to the current edited post or page. The quicktag is inserted at the cursor position.
*If a disabling quicktag already exists, this button removes it.*
* **Disable button**
Adds the disabling quicktag to the current edited post or page. The quicktag is inserted at the cursor position.
*If an enabling quicktag already exists, this button removes it.*
* **Remove button**
Removes any quicktag from the current edited post or page.

== Online Resources ==

If you have any questions that aren't addressed in this document, please visit [our website](http://www.xhaleera.com) and its [mAutoPopup dedicated section](http://www.xhaleera.com/index.php/products/wordpress-mseries-plugins/mautopopup/).

== Known issues ==

Here is the known cases that may prevent mAutoPopup to work properly :

* The Rich Text Editor plugin may not be available for some WordPress 2.0+ installations, though other functions of mAutoPopup work perfectly well. 

== License ==
mAutoPopup, as WordPress, is released under the terms of the GNU GPL v2 (see license.txt).
Permission to use, copy, modify, and distribute this software and its documentation under the terms of the GNU General Public License is hereby granted. No representations are made about the suitability of this software for any purpose. It is provided “as is” without express or implied warranty.
