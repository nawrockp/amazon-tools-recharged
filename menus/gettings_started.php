<div class="wrap" style="max-width: 600px;">

<div class="menu_icon icon32"><br /></div>
<h2>Getting Started</h2>
<br />
<h3>Getting Help</h3>
If you have any questions or need help using this plugin, you are encouraged to register on the <a href="http://forums.tinsology.net/">forum</a>.

<h3>First Step</h3>
Before you can start using this plugin, you will need to have an <a href="http://aws.amazon.com/">AWS account</a>. Once you have an account, locate
your Access Key and Secret Key and input them in the Amazon Tools Settings page. If you do not already have one, you will also need to register an
<a href="https://affiliate-program.amazon.com/">Amazon Associates</a> account and locate your associate tag.
<br /><br />
The access key, secret key, and associate tag are all required; the plugin cannot function without them.

<h3>ASINs</h3>
An ASIN is a ten character string used by Amazon to identify products. Amazon Tools uses ASINs to retrieve product information. You can locate a product's
ASIN in the product URL. For example in this URL: 
<pre>http://www.amazon.com/Kindle-Wireless-Reading-Display-Generation/dp/B002Y27P3M/</pre>
the ASIN is B002Y27P3M. If you're using Amazon Tools Quick Search to lookup products, the ASIN will be included in the search results.

<h3>Using Shortcodes</h3>
To use this plugin you will need to know how to use the <code>amazon</code> shortcode. You can find the shortcode documentation 
<a href="http://forums.tinsology.net/viewtopic.php?f=8&t=2">Here</a>. You will need to use shortcodes to display production information in posts
as well as create templates.

<h3>Shortcode Examples</h3>
<h5>Creating a product link</h5>
To create a link to a product use the <code>amazon</code> shortcode and specify the product's ASIN in the asin attribute, and 'link' in the get attribute.
<pre>
&lt;a href=&quot;[amazon asin=&quot;1439149038&quot; get=&quot;link&quot;]&quot;&gt;Under the Dome by Stephen King&lt;/a&gt;
</pre>

<h5>Link with product title</h5>
<pre>
&lt;a href=&quot;[amazon asin=&quot;0385533853&quot; get=&quot;link&quot;]&quot;&gt;[amazon get=&quot;title&quot; length=&quot;30&quot;]&lt;/a&gt;
</pre>
Notice that in the second shortcode, the ASIN <strong>is not</strong> specified. Once you specify an ASIN it will be used in every shortcode afterward,
unless you specify a new ASIN. Also notice that the second shortcode has a 'length' attribute. This attribute is used to limit the number of characters
displayed by the shortcode. When the length attribute cuts off part of the shortcode output it will append an ellipsis (&hellip;) to indiciate the text
was cut short.

<h5>Product Ad From List</h5>
The following example will select a product at random from a list (you can create lists in the Amazon Tools Lists page) and display a template for the
selected product. In this example, the list will be called 'books' and the template will be 'Simple Ad Unit' (one of the templates that comes with the plugin).
<pre>
[amazon list="books" template="Simple Ad Unit" random="1"]
</pre>

<h3>Creating Templates</h3>
The plugin comes with a few templates to get you started and serve as examples. However, it is likely that you will need to modify these templates
or create new ones to better suit your theme and needs. <a href="http://forums.tinsology.net/viewtopic.php?f=8&t=4&p=4">Creating Templates Guide</a>.

<h3>Show Off</h3>
Feel free to show others how you are using Amazon Tools by posting in the <a href="http://forums.tinsology.net/viewforum.php?f=12">Forum</a>.

<br />
<?php amazon_admin_footer(); ?>

</div>