=== Amazon Tools ===
Contributors: mtinsley
Donate link: http://tinsology.net/plugins/amazon-tools/
Tags: Amazon, Amazon.com, associate, AWS, ads, review, affiliate
Requires at least: 3.1
Tested up to: 3.2.1
Stable tag: 1.7.2

Amazon Tools allows you to quickly and easily retrieve Amazon product information and display it on your WordPress blog.

== Description ==

Amazon Tools is a plugin that allows you to integrate your Wordpress blog with Amazon Web Services. Using Amazon Tools 
you can quickly and easily retrieve product data from Amazon.com and display it on your blog. The plugin also supports
integration with Amazon Associates Program, allowing you to earn money by advertising Amazon products. You can use this
plugin to do something as simple as building an ad unit that will display products of your choice, or use your blog to
review products.

More information can be found [Here](http://tinsology.net/plugins/amazon-tools/)
The Premium version of Amazon Tools is available now. Download it [Here](http://wpamazon.com/go-premium/)

== Installation ==

1. Upload the `amazon-tools` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Once activated a new 'Amazon Tools' menu should appear in your admin area.
1. In order for the plugin to operate, you must have an AWS Access Key and Secret Key
1. Sign up for an [AWS account](http://aws.amazon.com/) if you haven't already.

== Frequently Asked Questions ==

= What is the difference between Amazon Tools and Amazon Tools Premium =

The premium version includes auto-linking and click tracking. Future version
of Amazon Tools Premium will also include features not included in the free version. You can download
Amazon Tools Premium [Here](http://tinsology.net/plugins/amazon-tools/).

= Where can I find more information and get help using Amazon Tools? =

The [Amazon Tools forum](http://forums.tinsology.net/index.php) contains plugin information as well as usage tutorials.
Feel free to sign up and request help if you are having trouble using the plugin. Also, please direct any bug reports,
suggestions, or comments to the forum.

= Does this plugin require an AWS account and an Amazon Associates account? =

This plugin cannot function without an AWS access key and secret key. You need an
AWS account to aquire these. The plugin does not require an associates account, but
if you do not have one you will not recieve any credit for referals. Your access
key, secret key, and associate tag can be specified in the Amazon settings page.

== Screenshots ==

1. The Amazon Tools menu should appear in your admin area after installing the plugin.
2. The plugin settings page. An AWS access key and secret key must be specified before the plugin will function.
3. The plugin allows you to create 'templates' that can be used with the amazon shortcode or by specifying a post template.
4. The plugin allows you to specify a template that will be applied to your post.
5. The Quick Search feature allows you to lookup products while writing posts

== Changelog ==

= 1.7.2 =
* Added an option to enable shortcodes in RSS feeds

= 1.7 =
* Made some changes to the UI to make it more compatible with WP 3.2

= 1.5.2 =
* Added support for Italy (IT)
* Upgraded to the latest API version
* Associate tag is now required. The plugin will not function without one.

= 1.5 =
* Added Lists
* Added 'Number of results' dropdown to Quick Search

= 1.4.2 =
* See [Version Notes](http://forums.tinsology.net/viewtopic.php?f=13&t=52)

= 1.4 =
* Made several enhancements to the foreach, similar, and random shortcodes.
* Added the 'Quick Search' feature
* For full version notes see: [Version Notes](http://forums.tinsology.net/viewtopic.php?f=13&t=32)

= 1.3 =
* Added support for custom post types
* Minor bug fixes

= 1.2 =
* Added the amazon_do_shortcode() function which allows you to parse shortcodes in your theme.
* Added the 'Use Excerpt Templates on Non-Single Pages' option which tells the plugins to use the excerpt field when applying post templates on non-single pages.
* Prevented post templates from being applied in feeds.

== Resources ==

Here are some links that will help you get started with Amazon Tools:

* [Plugin documentation](http://tinsology.net/plugins/amazon-tools/)
* [Using the amazon shortcode](http://forums.tinsology.net/viewtopic.php?f=8&t=2)
* [Basic template creation](http://forums.tinsology.net/viewtopic.php?f=8&t=4)
