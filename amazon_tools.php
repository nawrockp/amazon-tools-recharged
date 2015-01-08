<?php
/*
Plugin Name: Amazon Tools
Description: Amazon Tools is a plugin that allows you to integrate your Wordpress blog with Amazon Web Services. Using Amazon Tools you can quickly and easily retrieve product data from Amazon.com and display it on your blog.
Version: 1.7.2
Plugin URI: http://forums.tinsology.net/index.php
Author URI: http://tinsology.net
Author: Mathew Tinsley

/*
	Copyright 2010-2011 Mathew Tinsley (email: tinsley@tinsology.net)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
require_once('lib.php');
require_once('menus.php');
require_once('metabox.php');
require_once('ajax.php');
require_once('plugin_update.php');
class AmazonTools {

	private $allowed_settings = array('access_key');
	private $last_asin;
	private $ajax_update;
	
	public $url;
	public $dir;
	
	public $css_url;
	public $css_dir;
	
	public static $prefix = 'amazon_tools_';
	public static $version = '1.7.2';
	public static $version_url = 'http://forums.tinsology.net/viewtopic.php?f=13&t=670';
	public static $premium_url = 'http://wpamazon.com/go-premium/';
	public static $is_premium = false;
	
	public function __construct()
	{
		register_activation_hook( __FILE__, array($this, 'install') );
		register_deactivation_hook( __FILE__, array($this, 'uninstall') );
		
		if(AmazonLib::isActive())
		{
			add_shortcode('amazon', array($this, 'shortcode') );
			add_shortcode('amazon_i', array($this, 'shortcode') );
			add_shortcode('amazon_format', array($this, 'format') );
			
			add_filter('the_content', array($this, 'applyTemplate'), 15);
			add_filter('the_content', array($this, 'bufferData'), 16);
			
			if(get_option('amazon_excerpt_shortcodes') == 1)
			{
				add_filter('the_excerpt', array($this, 'bufferData'), 16);
				add_filter('the_excerpt', 'do_shortcode');
				add_filter('get_the_excerpt', array($this, 'applyTemplateExcerpt'), 1);
			}
			
			if(get_option('amazon_feed_shortcodes') == 1)
			{
				add_filter('the_content_feed', 'do_shortcode', 5);
				add_filter('the_content_feed', array($this, 'bufferData'), 6);
			}
			
			if(get_option('amazon_widget_shortcodes') == 1)
			{
				add_filter('widget_text', 'do_shortcode', 5);
				add_filter('widget_text', array($this, 'bufferData'), 6);
			}
		}
		add_action('wp_print_footer_scripts', array($this, 'ajaxUpdate'));
		add_action('init', array($this, 'enqueueScripts'));
		
		$this->url = plugins_url('', __FILE__);
		$this->dir = dirname(__FILE__);
		
		$this->css_url = get_option('amazon_global_css');
		$this->css_dir = get_option('amazon_global_css_dir');
		
		$this->ajax_update = false;
		
		global $amazon_locale;
		$locale = get_option('amazon_locale');
		$amazon_locale = $locale == '' ? 'us' : $locale;
		
		$version = get_option('amazon_version');
		if($version != '' && $version != AmazonTools::$version)
		{
			AmazonPluginUpdate::go($version, $this->dir . '/plugin-update');
			update_option('amazon_version', AmazonTools::$version);
			update_option('amazon_updated', 1);
		}
		if(get_option('amazon_updated'))
			add_action('admin_notices', array($this, 'updateNag'));
			
		if(AmazonTools::$is_premium && get_option('amazon_premium_update_url'))
		{
			$update_checker = new PluginUpdateChecker(
				$this->url . '/plugin-info.json.php?url=' . urlencode(get_option('amazon_premium_update_url')),
				__FILE__,
				'amazon-tools-premium'
			);

			//$update_checker->checkForUpdates();
		}
	}

	public function uninstall()
	{
		global $wpdb;
		$prefix = $wpdb->prefix;
		
		$cache 		= "DROP TABLE IF EXISTS {$prefix}amazon_cache;";
		$templates 	= "DROP TABLE IF EXISTS {$prefix}amazon_templates;";
		$fields 	= "DROP TABLE IF EXISTS {$prefix}amazon_template_fields;";
		$lists		= "DROP TABLE IF EXISTS {$prefix}amazon_lists;";
		
		$wpdb->query($cache);
		$wpdb->query($templates);
		$wpdb->query($fields);
		$wpdb->query($lists);
		
		delete_option('amazon_state');
		delete_option('amazon_expire');
		delete_option('amazon_error');
		delete_option('amazon_update_method');
		delete_option('amazon_feed_shortcodes');
		
		if(self::$is_premium)
			AmazonPremium::uninstall();
	}

	public function install()
	{
		global $wpdb;
		$prefix = $wpdb->prefix;
		
		add_option('amazon_state');
		
		ob_start();
		$wpdb->show_errors();
		
		$cache = "CREATE TABLE IF NOT EXISTS {$prefix}amazon_cache (
					asin CHAR(10) NOT NULL,
					field VARCHAR(32) NOT NULL,
					value TEXT,
					expire INT NOT NULL,
					PRIMARY KEY(asin, field)
				);";
				
		$templates = "CREATE TABLE IF NOT EXISTS {$prefix}amazon_templates (
						id INT PRIMARY KEY AUTO_INCREMENT,
						name VARCHAR(128),
						type VARCHAR(15),
						content TEXT,
						excerpt TEXT,
						css TEXT
					);";
					
		$template_fields = "CREATE TABLE IF NOT EXISTS {$prefix}amazon_template_fields (
								template INT,
								field_name VARCHAR(64),
								default_value TEXT,
								type VARCHAR(15),
								PRIMARY KEY(template, field_name)
					);";
					
		$lists = "CREATE TABLE IF NOT EXISTS {$prefix}amazon_lists (
					name VARCHAR(32),
					asin VARCHAR(10),
					PRIMARY KEY(name, asin)
				);";
					
		if($wpdb->query($cache) !== false && $wpdb->query($templates) !== false
			&& $wpdb->query($template_fields) !== false && $wpdb->query($lists) !== false)
		{
			$wpdb->hide_errors();
			update_option('amazon_state', 'ok');
		}
		else
		{
			$wpdb->hide_errors();
			update_option('amazon_state', 'install_failed');
			update_option('amazon_error', ob_get_clean());
		}
		
		if(get_option('amazon_state') == 'ok')
		{
			amazon_sample_templates();
		}
		
		add_option('amazon_expire', 3); //cache expire time, default 3 days
		add_option('amazon_update_method', 'ajax');
		add_option('amazon_install_time', time());
		add_option('amazon_widget_shortcodes', 1);
		add_option('amazon_excerpt_shortcodes', 0);
		add_option('amazon_locale', 'us');
		add_option('amazon_css_version', time());
		add_option('amazon_global_css');
		add_option('amazon_version', '1.4');
		add_option('amazon_stats_last', time() + 86400);
		add_option('amazon_excerpt_non_single', 1);
		add_option('amazon_getting_started', 1);
		add_option('amazon_feed_shortcodes', 0);
		
		if(self::$is_premium)
			AmazonPremium::install();
			
		update_option('amazon_updated', 1);
	}
	
	public function updateNag()
	{
?>
		<div class="updated fade">
			<p>
				Check out what's new in <strong><a href="<?php echo self::$version_url; ?>" target="_blank">Amazon Tools <?php echo self::$version; ?></a></strong>.
				<?php if(!self::$is_premium) : ?>
				<br /><strong>You are using the basic version of Amazon Tools.
				<br />Upgrade to <a href="<?php echo self::$premium_url; ?>" target="_blank">Amazon Tools Premium</a> and get access to additional features like <em>Revenue Sharing</em> and <em>Auto Linking</em>.
				<br />Now on sale for 20% off.</strong>
				<?php endif; ?>
			</p>
		</div>
<?php
		update_option('amazon_updated', 0);
	}
	
	public function triggerAjaxUpdate()
	{
		$this->ajax_update = true;
	}
	
	public function enqueueScripts()
	{
		wp_enqueue_script('jquery');
		if($this->css_url != '')
			wp_enqueue_style('amazon_global_css', $this->css_url, array(), get_option('amazon_css_version'));
	}
	
	public function ajaxUpdate()
	{
		if(!$this->ajax_update)
			return;
		
		$nonce = wp_create_nonce('amazon_update_nonce');
?>
<script type="text/javascript">
jQuery(document).ready(function($){var amazon_ajax_url='<?php echo admin_url('admin-ajax.php'); ?>';var data={action:'amazon_update',security:'<?php echo $nonce; ?>'};jQuery.post(amazon_ajax_url,data,function(response){});});
</script>
<?php
	}
	
	public function applyTemplate($content, $excerpt = false)
	{
		if(is_feed())
			return $content;
			
		global $post;
		$temp_id = get_post_meta($post->ID, 'amazon_post_template', true);
		
		if(!$temp_id)
			return $content;
			
		$template = amazon_get_template($temp_id);
		$temp_fields = amazon_get_template_fields($template['id']);
		$fields = array();
		
		foreach($temp_fields as $field)
		{
			$key = AmazonTools::$prefix . $field['field_name'];
			$meta_value = get_post_meta($post->ID, $key, true);
			//$fields[$field['field_name']] = ($meta_value != '') ? $meta_value : $field['default_value'];
			$fields[$field['field_name']] = $meta_value;
		}
		
		if($excerpt && trim($template['excerpt']) == '')
			return $content;

		$temp_content = ($excerpt || (get_option('amazon_excerpt_non_single') == 1 && !is_singular())) ? $template['excerpt'] : $template['content'];
		$temp_content = stripslashes($temp_content);
		
		foreach($fields as $key => $value)
		{
			$temp_content = str_replace('%' . $key . '%', $value, $temp_content);
		}
		
		$css = ($template['css'] == '') ? '' : "<style type=\"text/css\">\n{$template['css']}\n</style>\n";
		
		return $css . do_shortcode(str_replace('%content%', $content, $temp_content));
	}
	
	public function applyTemplateExcerpt($content)
	{
		if($content == '')
		{
			global $post;
			$content = $post->post_content;
		}
		return $this->applyTemplate($content, true);
	}
	
	public function shortcode($atts, $content = null)
	{
		if(isset($atts['similar']))
		{
			$asin = isset($atts['asin']) ? $atts['asin'] : $this->last_asin;
			$asins = AmazonLib::getSimilar($asin);
			if(!isset($atts['limit']) && is_numeric($atts['similar']))
				$atts['limit'] = $atts['similar'];
				
			$atts['foreach'] = @implode(',', $asins);
		}
		
		if(isset($atts['list']))
		{
			$asins = AmazonLib::getProductsByList(trim($atts['list']));
			$atts['foreach'] = @implode(',', $asins);
		}
		
		if(isset($atts['random']))
		{
			if($atts['random'] == '')
				$atts['random'] = -1;
			
			if(isset($atts['foreach']))
			{
				$asins = explode(',', $atts['foreach']);
				$c = (is_numeric($atts['random']) && strlen($atts['random']) < 10 && $atts['random'] != -1) ? $atts['random'] : count($asins);
			}
			elseif(is_numeric($atts['random']) && strlen($atts['random']) < 10) //asins are ten digits and can be numeric
			{
				$asins = explode(',', strip_tags($content));
				$c = (count($asins) < $atts['random'] || $atts['random'] == -1) ? count($asins) : $atts['random'];
			}
			else
			{
				$asins = explode(',', $atts['random']);
				$c = isset($atts['limit']) ? $atts['limit'] : 1;
			}
			shuffle($asins);
			$atts['foreach'] = @implode(',', array_slice($asins, 0 , $c));
		}
		
		if(isset($atts['foreach']))
		{
			if(trim($atts['foreach']) == '')
				return '';
				
			return $this->shortcodeForeach($atts, $content);
		}
			
		if(isset($atts['setting']))
		{
			$setting = $this->shortcodeSetting($atts['setting']);
			if($content == null || $setting == '')
				return $setting;
				
			return do_shortcode($content);
		}
		
		if(isset($atts['asin']) && $atts['asin'] != '%asin%')
			$this->last_asin = $atts['asin'];
		
		if(isset($atts['template']))
		{
			if($template = amazon_get_template($atts['template']))
			{
				$temp_content = str_replace('%asin%', $this->last_asin, stripslashes($template['content']));
				$css = ($template['css'] == '') ? '' : "<style type=\"text/css\">\n{$template['css']}\n</style>\n";
				
				return $css . do_shortcode($temp_content);
			}
			
			return do_shortcode($content);
		}
			
		if(isset($atts['get']))
		{
			global $amazon_buffer;
			$asin = (string) $this->last_asin;
			$get = isset($atts['get']) ? $atts['get'] : $content;
			
			$amazon_buffer[$asin][] = $get;
			
			$wrap_open = '';
			$wrap_close = '';
			
			if(isset($atts['length']))
			{
				$wrap_open = '[amazon_format length="' . $atts['length'] . '"]';
				$wrap_close = '[/amazon_format]';
			}
			
			return $wrap_open . '%' . $asin . '_' . $get . '%' . $wrap_close;
		}
		
		if(isset($atts['post']))
		{
			global $post;

			if(!$post)
				return do_shortcode($content);
				
			if(isset($post->$atts['post']))
				return $post->$atts['post'];
				
			if($atts['post'] == 'permalink')
				return get_permalink();
		}
		
		return do_shortcode($content);
	}
	
	/*
		This shortcode is used interally to format amazon data
	*/
	public function format($atts, $content = null)
	{
		if(!$content)
			return;
			
		if(isset($atts['length']) && is_numeric($atts['length']))
		{
			$append = '';
			if($atts['length'] < strlen($content))
				$append = '&hellip;';
			$content = substr($content, 0, $atts['length'] - 1) . $append;
		}
		
		return $content;
	}
	
	private function shortcodeForeach($atts, $content)
	{
		if($content == '' && !isset($atts['template']))
			return '';
			
		if(isset($atts['template']) && $template = amazon_get_template($atts['template']))
		{
			$temp_content = stripslashes($template['content']);
			$css = ($template['css'] == '') ? '' : "<style type=\"text/css\">\n{$template['css']}\n</style>\n";
			$content = $css . $temp_content;
		}
			
		$asin = explode(',', $atts['foreach']);
		
		if(!is_array($asin) || count($asin) == 0)
			return '';
		
		//check if there is a limit set
		if(isset($atts['limit']) && is_numeric($atts['limit']))
		{
			$limit = $atts['limit'];
			if(count($asin) > $limit)
				$asin = array_slice($asin, 0, $limit);
		}
		
		$processedContent = '';
		
		foreach($asin as $a)
		{
			$this->last_asin = $a;
			$curr = str_replace('%asin%', $a, $content);
			$processedContent .= do_shortcode($curr) . "\n";
		}
		
		return $processedContent;
	}
	
	private function shortcodeSetting($key)
	{
		if($key == 'tld')
		{
			global $amazon_locale;
			return AmazonAPI::getTLD($amazon_locale);
		}
		elseif($key == 'associate_tag')
		{
			return AmazonLib::assocTag();
		}
		elseif(!in_array($key, $this->allowed_settings))
			return '';
		else
			return get_option('amazon_' . $key);
	}
	
	public function bufferData($content)
	{
		global $amazon_buffer;
		global $amazon_locale;
		
		foreach($amazon_buffer as &$keys)
		{
			$keys = array_unique($keys);
		}
		
		//echo '<pre>'; print_r($amazon_buffer); echo '</pre>';
		
		if(count($amazon_buffer) < 1)
			return $content;
		
		$data = amazon_fetch($amazon_buffer, $amazon_locale);
		
		foreach($data as $asin => $keys)
		{
			foreach($keys as $key => $value)
			{
				$search = "%{$asin}_{$key}%";
				$content = str_replace($search, $value, $content);
			}
		}
		
		$amazon_buffer = array();
		
		/*
			This optimization avoids going through every shortcode again.
			The only shortcode we need to look for is 'amazon_format'.
		*/
		global $shortcode_tags;
		$t = $shortcode_tags;
		$shortcode_tags = array('amazon_format' => array($this, 'format'));
		
		$content = do_shortcode($content);
		$shortcode_tags = $t;
		
		return $content;
	}
}
if(AmazonTools::$is_premium)
	require_once('premium.php');
$amazon_locale = 'us';
$amazon_tools = new AmazonTools();

$amazon_buffer = array();
$amazon_enable_cache = true;
?>