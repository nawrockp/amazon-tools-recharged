<?php
class AmazonMenus {

	public function __construct()
	{
		add_action('admin_menu', array($this, 'adminMenus'));
		add_action('admin_init', array($this, 'registerScripts'));
		
		if(is_admin())
		{
			if( in_array( $_GET['page'], array('amazon_tools', 'amazon_getting_started', 'amazon_cache', 'amazon_templates', 'amazon_lists') ) )
			{
				if(!get_option('amazon_hide_upgrade_notice'))
					add_action('admin_notices', array($this, 'upgradeNotice'));
			}
		}
	}
	
	public function registerScripts()
	{
		wp_register_style('amazon_admin_css', plugins_url('/menus/style_main.css', __FILE__), array(), '1.1');
		wp_register_script('amazon_admin_js', plugins_url('/menus/lib.js', __FILE__), array('jquery'), '1.1');
		wp_register_script('tab-override', plugins_url('/menus/jquery.taboverride-1.0.min.js', __FILE__), array('jquery'));
		if(AmazonTools::$is_premium)
		{
			wp_register_script('amazon_chart_js', plugins_url('/menus/chart.js', __FILE__), array(), '1.0');
			wp_register_style('amazon_chart_css', plugins_url('/menus/chart.css', __FILE__));
		}
	}
	
	public function enqueueScripts()
	{
		wp_enqueue_script('jquery');
		wp_enqueue_script('amazon_admin_js');
		wp_enqueue_style('amazon_admin_css');
	}
	
	public function upgradeNotice()
	{
		if(isset($_GET['hide-upgrade-notice']) && $_GET['hide-upgrade-notice'] == 1)
			update_option('amazon_hide_upgrade_notice', 1);
		else
		{
	?>
		<div class="updated fade">
			<p>
				<strong>You are using the basic version of Amazon Tools.
				<br />Upgrade to <a href="<?php echo AmazonTools::$premium_url; ?>" target="_blank">Amazon Tools Premium</a> and get access to additional features like <em>Revenue Sharing</em> and <em>Auto Linking</em>.</strong>
				<br /><a href="?page=<?php echo $_GET['page']; ?>&hide-upgrade-notice=1">Remove this notice</a>.
			</p>
		</div>
	<?php
		}
	}
	
	public function settingsInit()
	{
		add_action('admin_print_footer_scripts', array($this, 'settingsJS'));
	}
	
	public function templatesInit()
	{
		wp_enqueue_script('tab-override');
		add_action('admin_print_footer_scripts', array($this, 'templatesJS'));
	}
	
	public function clickInit()
	{
		wp_enqueue_script('amazon_chart_js');
		wp_enqueue_style('amazon_chart_css');
		add_action('admin_print_footer_scripts', array($this, 'clickJS'));
	}
	
	public function cacheInit()
	{
		add_action('admin_print_footer_scripts', array($this, 'cacheJS'));
	}
	
	public function listsInit()
	{
		global $amazon_tools;
		add_action('admin_print_footer_scripts', array($this, 'listsJS'));
		wp_enqueue_style('amazon_meta_css', $amazon_tools->url . '/menus/style_meta.css');
	}
	
	//Admin menus
	public function adminMenus()
	{
		add_menu_page('Amazon Tools', 'Amazon Tools', 'manage_options', 'amazon_tools', array($this, 'displaySettings'), plugins_url('menus/img/icon_sm.png', __FILE__));
		//override the default page
		$settings 	= add_submenu_page('amazon_tools', 'Settings', 'Settings', 'manage_options', 'amazon_tools', array($this, 'displaySettings'));
		
		if(get_option('amazon_getting_started') == 1)
		{
			$start = add_submenu_page('amazon_tools', 'Getting Started', 'Getting Started', 'manage_options', 'amazon_getting_started', array($this, 'displayGettingStarted'));
			add_action('admin_print_styles-' . $start, array($this, 'enqueueScripts'));
		}
		
		$templates 	= add_submenu_page('amazon_tools', 'Templates', 'Templates', 'manage_options', 'amazon_templates', array($this, 'displayTemplates'));
		$cache		= add_submenu_page('amazon_tools', 'Cache', 'Cache', 'manage_options', 'amazon_cache', array($this, 'displayCache'));
		$lists		= add_submenu_page('amazon_tools', 'Lists', 'Lists', 'manage_options', 'amazon_lists', array($this, 'displayLists'));
		if(AmazonTools::$is_premium && get_option('amazon_click_tracking') == 1)
		{
			$click	= add_submenu_page('amazon_tools', 'Clicks', 'Clicks', 'manage_options', 'amazon_clicks', array($this, 'displayClicks'));
			add_action('admin_print_styles-' . $click, array($this, 'enqueueScripts'));
			add_action('admin_print_styles-' . $click, array($this, 'clickInit'));
		}
		
		add_action('admin_print_styles-' . $settings, array($this, 'enqueueScripts'));
		add_action('admin_print_styles-' . $settings, array($this, 'settingsInit'));
		
		add_action('admin_print_styles-' . $templates, array($this, 'enqueueScripts'));
		add_action('admin_print_styles-' . $templates, array($this, 'templatesInit'));
		
		add_action('admin_print_styles-' . $cache, array($this, 'enqueueScripts'));
		add_action('admin_print_styles-' . $cache, array($this, 'cacheInit'));
		
		add_action('admin_print_styles-' . $lists, array($this, 'enqueueScripts'));
		add_action('admin_print_styles-' . $lists, array($this, 'listsInit'));
		
		//add_action('admin_print_styles-' . $shortcodes, array($this, 'enqueueScripts'));
	}
	
	public function displaySettings()
	{
		include('menus/settings.php');
	}
	
	public function settingsJS()
	{
?>
<script type="text/javascript">
var $amazon_jq=jQuery.noConflict();var amazon_lib=new AmazonLib();amazon_lib.fieldInfo('#update_method_link','#update_method_info');amazon_lib.fieldInfo('#cache_expire_link','#cache_expire_info');amazon_lib.fieldInfo('#associate_tag_link','#associate_tag_info');amazon_lib.fieldInfo('#access_key_link','#access_key_info');amazon_lib.fieldInfo('#shortcode_widget_link','#shortcode_widget_info');amazon_lib.fieldInfo('#locale_link','#locale_info');amazon_lib.fieldInfo('#shortcode_excerpt_link','#shortcode_excerpt_info');amazon_lib.fieldInfo('#non_single_link','#non_single_info');amazon_lib.fieldInfo('#stats_link','#stats_info');amazon_lib.fieldInfo('#post_types_link','#post_types_info');amazon_lib.fieldInfo('#download_url_link','#download_url_info');
</script>
<?php
	}
	
	public function templatesJS()
	{
?>
<script type="text/javascript">
var $amazon_jq=jQuery.noConflict();var amazon_lib=new AmazonLib();$amazon_jq('textarea').tabOverride(true);$amazon_jq('#add_field').click(amazon_lib.addField);
</script>
<?php
	}
	
	public function cacheJS()
	{
?>
<script type="text/javascript">
var $amazon_jq=jQuery.noConflict();var amazon_lib=new AmazonLib();$amazon_jq('.cache_expand').click(amazon_lib.expand);
</script>
<?php
	}
	
	public function clickJS()
	{
		$nonce = wp_create_nonce('amazon_day_stats');
?>
<script type="text/javascript">
var amazon_nonce = '<?php echo $nonce; ?>';
var amazon_ajax_url = '<?php echo admin_url('admin-ajax.php'); ?>';
</script>
<?php
	}
	
	public function listsJS()
	{
?>
<script type="text/javascript">
		var $amazon_jq = jQuery.noConflict();
		var amazon_lib = new AmazonLib();
		var amazon_nonce = '<?php echo wp_create_nonce('amazon_tools_nonce'); ?>';
		$amazon_jq('[name="new_list"]').focus(function(){
			if($amazon_jq('[name="new_list"]').val() == 'New List')
			{
				$amazon_jq('[name="new_list"]').css('color', '#000000');
				$amazon_jq('[name="new_list"]').css('font-style', 'normal');
				$amazon_jq('[name="new_list"]').val('');
			}
		});
		$amazon_jq('[name="new_list"]').blur(function(){
			if($amazon_jq('[name="new_list"]').val().replace('/\s/g','') == '')
			{
				$amazon_jq('[name="new_list"]').css('color', '#6b6b6b');
				$amazon_jq('[name="new_list"]').css('font-style', 'italic');
				$amazon_jq('[name="new_list"]').val('New List');
			}
		});
		$amazon_jq('#amazon_search_btn').click(amazon_lib.search);
		$amazon_jq('#new_list_form').submit(amazon_lib.checkListForm);
		$amazon_jq('.remove_asin_btn').click(amazon_lib.removeASIN);
		$amazon_jq('[name="add_product"]').click(amazon_lib.addASIN);
		$amazon_jq('.delete_list').click(amazon_lib.deleteList);
</script>
<?php
	}
	
	public function displayTemplates()
	{
		include('menus/templates.php');
	}
	
	public function displayShortcodes()
	{
		include('menus/shortcodes.php');
	}
	
	public function displayCache()
	{
		include('menus/cache.php');
	}
	
	public function displayLists()
	{
		include('menus/lists.php');
	}
	
	public function displayClicks()
	{
		include('menus/click.php');
	}
	
	public function displayGettingStarted()
	{
		include('menus/gettings_started.php');
	}
}
new AmazonMenus();