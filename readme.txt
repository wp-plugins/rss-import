=== RSSImport ===
Contributors: Bueltge
Donate link: http://bueltge.de/wunschliste/
Tags: rss, post, content, post, feed
Requires at least: 1.5
Tested up to: 2.8

Import and display Feeds in your blog, use PHP or the Shortcode.

== Description ==
Import and display Feeds in your blog, use PHP or the Shortcode.

Use following code with a PHP-Plugin or in a template, example `sidebar.php` or `single.php`, for WordPress:

_Example:_
`&lt;?php RSSImport(10, 'http://bueltge.de/feed/'); ?&gt;`

This is smallest code for use the plugin with your own feed-url. The plugin have many paramters for custom import of content form a feed. See the list of paramters. You can also use all paramters with shorcode in posts and pages.

_Example for Shortcode:_
[RSSImport display="5" feedurl="http://bueltge.de/feed/"]

1. `display` - How many items, Default is `5`
1. `feedurl` - Feed-Adress, Default is `http://bueltge.de/feed/`
1. `before_desc` - string before description, Default is `empty`
1. `displaydescriptions` - (bool) true or false for display description of the item, Default is `false`
1. `after_desc` - string after description, Default is `empty`
1. `html` - (bool) display description include HTML-tags, Default is `false`
1. `truncatedescchar` - truncate description, number of chars, Default is `200`, set the value to empty `''` for non truncate
1. `truncatedescstring` - string after truncate description, Default is ` ... `
1. `truncatetitlechar` - (int) truncate title, number of chars, Default is `empty`, set a integer `50` to the value for truncate
1. `truncatetitlestring` - string after truncate title, Default is `' ... '`
1. `before_date` - string before date, Default is ` <small>`
1. `date` - (bool) return the date of the item, Default is `false`
1. `after_date` - string after the date, Default is `</small>`
1. `before_creator` - string before creator of the item, Default is ` <small>`
1. `creator` - (bool) return the creator of th item, Default is `false`
1. `after_creator` - string after creator of the item, Default is `</small>`
1. `start_items` - string before all items, Default is `<ul>`
1. `end_items` - string after all items, Default is `</ul>`
1. `start_item` - string before the item, Default is `<li>`
1. `end_item` - string after the items, Default is `</li>`
1. `target` - string with the target-attribut, Default is `empty`; use `blank`, `self`, `parent`, `top`
1. `charsetscan` - Scan for charset-type, load slowly; use this for problems with strings on the return content, Default is `false`
1. `debug` - activate debug-mode, echo the array of Magpie-Object; Default is `false`, Use only for debug purpose
1. `view` - echo or return the content of the function `RSSImport`, Default is `true`; Shortcode Default is `false`

All paramters it is possible to use in the function, only in templates with PHP, and also with the Shortcode in posts and pges.

= Examples: =

_The function with all paramters:_

`RSSImport(
`						$display = 5, $feedurl = 'http://bueltge.de/feed/',`
`						$before_desc = '', $displaydescriptions = false, $after_desc = '', $html = false, $truncatedescchar = 200, $truncatedescstring = ' ... ',`
`						$truncatetitlechar = '', $truncatetitlestring = ' ... ',`
`						$before_date = ' <small>', $date = false, $after_date = '</small>',`
`						$before_creator = ' <small>', $creator = false, $after_creator = '</small>',`
`						$start_items = '<ul>', $end_items = '</ul>',`
`						$start_item = '<li>', $end_item = '</li>'`
`					)`

_The shortcode with a lot of paramters:_

`[RSSImport display="10", feedurl="http://your_feed_url/", `
`displaydescriptions="true", html="true"`
`start_items="<ol>", end_items="</ol>" ]`

Please visit [the official website](http://bueltge.de/wp-rss-import-plugin/55/ "RSSImport") for further details and the latest information on this plugin.

== Installation ==
1. Unpack the download-package
1. Upload all files to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Create a new site in WordPress or edit your template
1. Copy the code in site-content or edit templates

See on [the official website](http://bueltge.de/wp-rss-import-plugin/55/ "RSSImport").

== Frequently Asked Questions ==

= Where can I get more information? =
Please visit [the official website](http://bueltge.de/wp-rss-import-plugin/55/ "RSSImport") for the latest information on this plugin.

= I love this plugin! How can I show the developer how much I appreciate his work? =
Please visit [the official website](http://bueltge.de/wp-rss-import-plugin/55/ "RSSImport") and let him know your care or see the [wishlist](http://bueltge.de/wunschliste/ "Wishlist") of the author.

== Make more with the plugin ==
Please visit [RSSImportTwo](http://bueltge.de/wp-rssimporttwo-plugin/165/ "RSSImportTwo") for more features and tutorial to import with more HTML. It give it a tutorial and a plugin.
