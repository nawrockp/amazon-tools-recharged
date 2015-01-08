<?php
global $amazon_tools;
define('RADIO_CHECKED', ' checked="checked" ');
if($_GET['settings-updated'] == 'true')
{
	$message = 'Settings Saved';
}
?>
<div class="wrap">

<div class="menu_icon icon32"><br /></div>
<h2>Settings</h2>

<?php if(isset($message)) : ?>
<div class="updated"><p><strong><?php echo $message; ?></strong></p></div> <br />
<?php endif; ?>

<form method="post" action="options.php">
<?php wp_nonce_field('update-options'); ?>

<table class="form-table">
<thead>
	<tr>
	<td style="width: 220px;"></td>
	<td style="width: 180px;"></td>
	<td></td>
	</tr>
</thead>
<tr valign="top">
<th scope="row" style="text-align:center"><strong>Settings</strong></th>
<td></td>
<td></td>
</tr>


<tr valign="top">
<th scope="row">Show 'Getting Started' Page</th>
<td><input type="checkbox" value="1" name="amazon_getting_started"<?php echo get_option('amazon_getting_started') == 1 ? RADIO_CHECKED : ''; ?>/></td>
<td>
</td>
</tr>

<?php if(AmazonTools::$is_premium) : ?>
<tr valign="top">
<th scope="row">Download Link</th>
<td><input type="text" name="amazon_premium_update_url" value="<?php echo get_option('amazon_premium_update_url'); ?>" /></td>
<td>
<a class="field_info_img" id="download_url_link"><img src="<?php echo $amazon_tools->url; ?>/menus/img/info.gif" width="20" height="20" alt="info" /></a> <br />
<div id="download_url_info" class="field_info">
In order to enable automatic updates, you must input the link that was emailed to you when you purchased Amazon Tools Premium. <strong>Note: </strong> If updating fails,
make sure the 'plugins' and 'amazon-tools-premium' directories are writable by PHP.
</div>
</td>
</tr>
<?php endif; ?>

<tr valign="top">
<th scope="row">AWS Access Key</th>
<td><input type="text" name="amazon_access_key" value="<?php echo get_option('amazon_access_key'); ?>" /></td>
<td>
<a class="field_info_img" id="access_key_link"><img src="<?php echo $amazon_tools->url; ?>/menus/img/info.gif" width="20" height="20" alt="info" /></a> <br />
<div id="access_key_info" class="field_info">
Your Access Key and Secret Key can be located in the <em>Security Credentials</em> section under the Account tab after logging into your Amazon Web Services account.
You may need to create a new access key if one does not exist.
</div>
</td>
</tr>

<tr valign="top" >
<td colspan="3">
Sign up for an <a href="http://aws.amazon.com/">AWS Account</a> 
<a href="http://aws.amazon.com/" class="new_window"></a> to get an access key
</td>
</tr>

<tr valign="top">
<th scope="row">AWS Secret Key</th>
<td><input type="text" name="amazon_secret_key" value="<?php echo get_option('amazon_secret_key'); ?>" /></td>
<td></td>
</tr>

<tr valign="top">
<th scope="row">Associate Tag</th>
<td><input type="text" name="amazon_associate_tag" value="<?php echo get_option('amazon_associate_tag'); ?>" /></td>
<td>
<a class="field_info_img" id="associate_tag_link"><img src="<?php echo $amazon_tools->url; ?>/menus/img/info.gif" width="20" height="20" alt="info" /></a> <br />
<div id="associate_tag_info" class="field_info">
Your Associate Tag, also referred to as Associate ID or Tracking ID, can be found near the top left corner of the page after logging into your amazon associates account. Example: <br />
<img src="<?php echo $amazon_tools->url; ?>/menus/img/associate-tag.png" width="192" height="118" /> <br />
The plugin requires an associate tag to function.
</div>
</td>
</tr>

<tr valign="top" >
<td colspan="3">
Sign up for an <a href="https://affiliate-program.amazon.com/">Amazon Associates Account</a> 
<a href="https://affiliate-program.amazon.com/" class="new_window"></a> to get an associate tag
</td>
</tr>

<tr valign="top">
<th scope="row">Locale</th>
<td>
<select name="amazon_locale">
<?php 
$locales = AmazonAPI::$locales;
$curr_locale = get_option('amazon_locale');
if(is_array($locales)) : foreach($locales as $locale) : 
?>
<option value="<?php echo strtolower($locale); ?>"<?php echo $curr_locale == strtolower($locale) ? ' selected="selected"' : ''; ?>><?php echo $locale; ?></option>
<?php endforeach; endif; ?>
</select>
</td>
<td>
<a class="field_info_img" id="locale_link"><img src="<?php echo $amazon_tools->url; ?>/menus/img/info.gif" width="20" height="20" alt="info" /></a> <br />
<div id="locale_info" class="field_info">
This is the locale that you want to serve content for.
</div>
</td>
</tr>

<tr valign="top">
<th scope="row">Cache Expire Time (days)</th>
<td><input type="text" name="amazon_expire" style="width: 36px;" value="<?php echo get_option('amazon_expire'); ?>" /></td>
<td>
<a class="field_info_img" id="cache_expire_link"><img src="<?php echo $amazon_tools->url; ?>/menus/img/info.gif" width="20" height="20" alt="info" /></a> <br />
<div id="cache_expire_info" class="field_info">
Product information retrieved from Amazon is cached in order to maintain site performance.
Cached data must be updated periodically, otherwise the product information served by your site might
not match the information on Amazon.com. Setting the expiration time too high may cause the plugin to
display outdated information. Lower values will increase the number of update requests and can increase
server load. <br /> <em>Default value:</em> 3
</div>
</td>
</tr>

<tr valign="top">
<th scope="row">Update Method</th>
<?php $amazon_update_method = get_option('amazon_update_method'); ?>
<td>
	<input type="radio" name="amazon_update_method" value="init"<?php if($amazon_update_method == 'init') echo RADIO_CHECKED; ?>/> Initial Request <br />
	<input type="radio" name="amazon_update_method" value="ajax"<?php if($amazon_update_method == 'ajax') echo RADIO_CHECKED; ?>/> Ajax <br />
	<input type="radio" name="amazon_update_method" value="cron"<?php if($amazon_update_method == 'cron') echo RADIO_CHECKED; ?>/> Cron <br />
</td>
<td class="field_info_td">
<a class="field_info_img" id="update_method_link"><img src="<?php echo $amazon_tools->url; ?>/menus/img/info.gif" width="20" height="20" alt="info" /></a> <br />
<div id="update_method_info" class="field_info">
<strong>Initial Request:</strong> Expired content is updated before sending output to the user. <br />
<em>Advantages:</em> Guarantees expired data is not sent to the user <br />
<em>Disadvantages:</em> Increases page load time for the visitor that triggers an update <br />
<br />
<strong>Ajax:</strong> Updates are triggered by an ajax request after the content has been sent to the user. <br />
<em>Advantages:</em> Updates are triggered <em>after</em> content is sent to the user. <br />
<em>Disadvantages:</em> Requires the user to have javascript enabled to trigger updates. <br />
<br />
<strong>Cron:</strong> An update script is triggered periodically by a crontab or schedule task. Script Path:<br />
<code><?php echo $amazon_tools->dir . '/update.php'; ?></code><br />
<em>Advantages:</em> Updates do not depend on traffic and do not affect a user's request. <br />
<em>Disadvantages:</em> Requires additional configuration.
</div>
</td>
</tr>

<tr valign="top">
<th scope="row">Enable Shortcodes in Widgets</th>
<td><input type="checkbox" value="1" name="amazon_widget_shortcodes"<?php echo get_option('amazon_widget_shortcodes') == 1 ? RADIO_CHECKED : ''; ?>/></td>
<td>
<a class="field_info_img" id="shortcode_widget_link"><img src="<?php echo $amazon_tools->url; ?>/menus/img/info.gif" width="20" height="20" alt="info" /></a> <br />
<div id="shortcode_widget_info" class="field_info">
Checking this box allows you to use shortcodes, including the 'amazon' shortcodes provided by this plugin, in sidebar widgets.
</div>
</td>
</tr>

<tr valign="top">
<th scope="row">Enable Shortcodes in Excerpts</th>
<td><input type="checkbox" value="1" name="amazon_excerpt_shortcodes"<?php echo get_option('amazon_excerpt_shortcodes') == 1 ? RADIO_CHECKED : ''; ?>/></td>
<td>
<a class="field_info_img" id="shortcode_excerpt_link"><img src="<?php echo $amazon_tools->url; ?>/menus/img/info.gif" width="20" height="20" alt="info" /></a> <br />
<div id="shortcode_excerpt_info" class="field_info">
Checking this box enables shortcodes in excerpts. Note that 'excerpt' refers to the content generated by the 'the_excerpt()' function. 
It does not include post summaries generated with the &lt;!--more--&gt; tag. Not all themes use excerpts; if your theme does not there
is no reason to check this box (Enable 'Use Excerpt Templates on Non-Single Pages' instead).
</div>
</td>
</tr>

<tr valign="top">
<th scope="row">Enable Shortcodes in Feeds</th>
<td><input type="checkbox" value="1" name="amazon_feed_shortcodes"<?php echo get_option('amazon_feed_shortcodes') == 1 ? RADIO_CHECKED : ''; ?>/></td>
<td>
</td>
</tr>

<tr valign="top">
<th scope="row">Use Excerpt Templates on Non-Single Pages</th>
<td><input type="checkbox" value="1" name="amazon_excerpt_non_single"<?php echo get_option('amazon_excerpt_non_single') == 1 ? RADIO_CHECKED : ''; ?>/></td>
<td>
<a class="field_info_img" id="non_single_link"><img src="<?php echo $amazon_tools->url; ?>/menus/img/info.gif" width="20" height="20" alt="info" /></a> <br />
<div id="non_single_info" class="field_info">
This setting is useful if your blog does not use the_excerpt(). If checked the plugin will use the excerpt field of post templates on non-single pages.
</div>
</td>
</tr>

<tr valign="top">
<th scope="row">Post Types</th>
<td>
<?php 
$types = get_post_types();
$exclude = array('attachment', 'revision', 'nav_menu_item');
$types = array_diff($types, $exclude);
$checked = get_option('amazon_post_types');
$checked = is_array($checked) ? $checked : array();
?><table class="post_type_table"><?php
foreach($types as $type) : 
?>
	<tr>
		<td><?php echo $type; ?></td>
		<td><input style="" type="checkbox" value="<?php echo $type; ?>" name="amazon_post_types[]"<?php echo in_array($type, $checked) ? RADIO_CHECKED : ''; ?>/></td>
	</tr>
<?php endforeach; ?>
</table>
</td>

<td>
<a class="field_info_img" id="post_types_link"><img src="<?php echo $amazon_tools->url; ?>/menus/img/info.gif" width="20" height="20" alt="info" /></a> <br />
<div id="post_types_info" class="field_info">
Select the post types you would like to use with Amazon Tools.
</div>
</td>
</tr>

<tr valign="top">
<th scope="row">Enable Click Tracking</th>
<?php if(AmazonTools::$is_premium) : ?>
<td><input type="checkbox" value="1" name="amazon_click_tracking"<?php echo get_option('amazon_click_tracking') == 1 ? RADIO_CHECKED : ''; ?>/></td>
<?php else : ?>
<td><em>Click Tracking is available in Amazon Tools Premium.<br />
<a href="<?php echo AmazonTools::$premium_url; ?>">Click Here</a> <a href="<?php echo AmazonTools::$premium_url; ?>" class="new_window"></a> to upgrade to premium.</em></td>
<?php endif; ?>
<td>
</td>
</tr>

</table>

<input type="hidden" name="action" value="update" />
<input type="hidden" name="page_options" value="amazon_access_key,amazon_secret_key,amazon_associate_tag,amazon_expire,
												amazon_update_method,amazon_widget_shortcodes,amazon_locale,amazon_excerpt_shortcodes,
												amazon_excerpt_non_single,amazon_post_types,amazon_click_tracking,amazon_premium_update_url,
												amazon_getting_started,amazon_feed_shortcodes" />

<p class="submit">
<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
</p>

</form>

<?php amazon_admin_footer(); ?>

</div>