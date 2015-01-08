<?php
class AmazonAjax
{
	public function __construct()
	{
		add_action('wp_ajax_amazon_change_template', array($this, 'changeTemplate'));
		add_action('wp_ajax_nopriv_amazon_update', array($this, 'doUpdate'));
		add_action('wp_ajax_amazon_update', array($this, 'doUpdate'));
		add_action('wp_ajax_amazon_search', array($this, 'search'));
		add_action('wp_ajax_amazon_remove_list_item', array($this, 'removeListItem'));
		add_action('wp_ajax_amazon_add_list_item', array($this, 'addListItem'));
		add_action('wp_ajax_amazon_delete_list', array($this, 'removeList'));
	}
	
	public function removeList()
	{
		if(!isset($_POST['list']))
			exit;
			
		$list = $_POST['list'];
		
		global $wpdb;
		$query = "DELETE FROM {$wpdb->prefix}amazon_lists WHERE name = '%s';";
		
		$wpdb->query( $wpdb->prepare($query, $list) );
	}
	
	public function addListItem()
	{
		if(!isset($_POST['list']) || !isset($_POST['asins']))
			exit;
			
		$list = $_POST['list'];
		$asins = $_POST['asins'];
		
		$asins = explode(',', $asins);
		if(!is_array($asins) || count($asins) == 0)
			exit;
			
		$in = array();
		
		foreach($asins as $asin)
			$in[] = '\'' . trim($asin) . '\'';

		$in = implode(', ', $in);
		
		global $wpdb;
		$query = "SELECT asin FROM {$wpdb->prefix}amazon_lists WHERE name = '$list' AND asin IN($in);";
		$existing = $wpdb->get_col($query);
		
		$asins = array_diff($asins, $existing);
			
		$inserted = array();
		AmazonLib::addToList($list, $asins, $inserted);
		
		if(count($inserted) == 0)
			exit;
		
		AmazonLib::drawListTableRows($list, $inserted);
		
		exit;
	}
	
	public function removeListItem()
	{
		if(!isset($_POST['list']) || !isset($_POST['asin']))
			exit;
		AmazonLib::removeListItem($_POST['list'], $_POST['asin']);
		exit;
	}
	
	public function search()
	{
		global $amazon_tools, $amazon_locale;
		
		check_ajax_referer('amazon_tools_nonce', 'security');
		$query = $_POST['search'];
		$count = $_POST['count'];
		$count = is_numeric($count) && $count > 0 ? $count : 3;
		
		update_option('amazon_quick_search_count', $count);
		
		if($query == '')
		{
			echo '<em>Search field cannot be blank</em>';
			exit;
		}
		
		$query = str_replace(' ', '%20', $query);		
		$asins = AmazonLib::quickSearch($query, $amazon_locale);
		
		if(!is_array($asins) || count($asins) == 0)
		{
			echo "<em>The search '$query' did not match any products.</em>";
			exit;
		}
		
		$asins = implode(',', array_slice($asins, 0, $count));
		ob_start();
?>
<div class="amazon_search_success">
[amazon_i foreach="<?php echo $asins; ?>"]
<div class="amazon_result">
	<a href="[amazon get="link"]" class="amazon_search_title" target="_blank">[amazon get="title" length="45"]</a>
	<a href="[amazon get="link"]" target="_blank"><img src="[amazon get="image"]" /></a> <br />
	ASIN: <strong>%asin%</strong>
</div>
[/amazon_i]
<br style="clear:both;" />
<div class="amazon_full_results">
<a target="_blank" href="http://www.amazon<?php echo AmazonAPI::getTLD($amazon_locale); ?>/s?ie=UTF8&keywords=<?php echo $query; ?>&tag=wpamazontools-20&index=blended">View results @ Amazon.com</a>
</div>
</div>
<?php
		$template = ob_get_clean();
		
		amazon_do_shortcode($template);
		exit;
	}
	
	public function changeTemplate()
	{
		check_ajax_referer('amazon_tools_nonce', 'security');
		$curr 		= $_POST['current_template'];
		$new 		= $_POST['new_template'];
		$post_id 	= $_POST['post_id'];
		$fields 	= $_POST['fields'];
		
		if(!is_array($fields))
			$fields = array();
		
		amazon_display_fields($post_id, $new, $fields);
		
		//print_r($_POST);
		exit;
	}
	
	public function doUpdate()
	{
		check_ajax_referer('amazon_update_nonce', 'security');
		
		global $wpdb;
		$table = $wpdb->prefix . 'amazon_cache';
		
		$time = time();
		$query = "SELECT asin, field FROM $table WHERE expire < '$time' ORDER BY asin LIMIT 6;";
		
		$res = $wpdb->get_results($query, ARRAY_A);
		
		$data = array();
		
		foreach($res as $row)
		{
			extract($row);
			if(count($data) == 3 && !isset($data[$asin]))
			{
				break; //I'm ashamed of myself 
			}
			
			if(!isset($data[$asin]))
				$data[$asin] = array();
				
			$data[$asin][] = $field;
		}
		
		global $amazon_locale;
		$junk = amazon_fetch($data, $amazon_locale, true);
		
		exit;
	}
}

new AmazonAjax();
?>