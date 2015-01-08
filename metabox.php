<?php
class AmazonMetaBox
{
	private $display_fields;
	private $post_id;
	
	public function __construct()
	{
		add_action('add_meta_boxes', array($this, 'prepareBox'));
		add_action('add_meta_boxes', array($this, 'prepareQuickSearch'));
		add_action('save_post', array($this, 'savePost'));
		add_action('admin_init', array($this, 'adminJS'));
		$this->display_fields = false;
	}
	
	public function adminJS()
	{
		global $pagenow, $amazon_tools;
		if($pagenow == 'post-new.php' || $pagenow == 'post.php')
		{
			wp_enqueue_script('amazon_admin_js');
			wp_enqueue_style('amazon_meta_css', $amazon_tools->url . '/menus/style_meta.css');
			add_action('admin_footer', array($this, 'adminFooter'));
		}
	}
	
	public function adminFooter()
	{
		$nonce = wp_create_nonce('amazon_tools_nonce');
?>
<script type="text/javascript">
var $amazon_jq=jQuery.noConflict();var amazon_lib=new AmazonLib();var amazon_nonce='<?php echo $nonce; ?>';var amazon_post_id='<?php echo $this->post_id; ?>';$amazon_jq('[name="amazon_post_template_select"]').change(amazon_lib.selectTemplate);$amazon_jq('#amazon_search_btn').click(amazon_lib.search);<?php if(!$this->display_fields):?>$amazon_jq('#amazon_tools_template_fields').css('display','none');<?php else:?>$amazon_jq('#amazon_tools_template_fields').css('display','block');<?php endif;?>
</script>
<?php
	}
	
	public function prepareQuickSearch()
	{
		$types = get_option('amazon_post_types');
		if(is_array($types))
		{
			foreach($types as $type)
			{
				add_meta_box('amazon_tools_quick_search', 'Amazon Tools Quick Search', 
							array($this, 'quickSearchBox'), $type, 'normal', 'high'
							);
			}
		}
	}
	
	public function prepareBox()
	{
		$templates = amazon_get_post_templates();
		if(is_array($templates) && count($templates) > 0)
		{
			global $pagenow;
			$args = array('templates' => $templates);
			
			if($pagenow == 'post.php')
			{
				$post_id = isset($_GET['post']) ? $_GET['post'] : $_POST['post_ID'];
				$this->post_id = $post_id;
				$template_id = get_post_meta($post_id, 'amazon_post_template', true);
				if($template_id)
				{
					$fields = amazon_get_template_fields($template_id);
					if(count($fields) > 0)
						$this->display_fields = true;
				}
				else
					$fields = array();
					
				$args['template_id'] = $template_id;
				$args['template_fields'] = $fields;
			}
			
			$types = get_option('amazon_post_types');
			if(is_array($types))
			{
				foreach($types as $type)
				{
					add_meta_box('amazon_tools_templates', 'Amazon Tools Post Templates', 
								array($this, 'templatesBox'), $type, 'side', 'core', $args
								);
								
					add_meta_box('amazon_tools_template_fields', 'Template Fields',
								array($this, 'fieldBox'), $type, 'normal', 'high', $args
								);
				}
			}
			
			//add_meta_box('amazon_tools_test', 'Test Box', array($this, 'testBox'), 'post', 'advanced', 'default', $args);
		}
	}
	
	public function quickSearchBox($post = null, $metabox = null)
	{
		$search_count = get_option('amazon_quick_search_count');
		?>
		<input type="text" name="amazon_search" style="width:180px;" />
		<input type="button" class="button-secondary" value="Search" id="amazon_search_btn" />
		Number of results <select name="amazon_search_count">
			<?php
			foreach(array('3', '5', '10') as $count) :
				if($search_count == $count) : 
					$selected = ' selected="selected"';
				else:
					$selected = '';
				endif;
				echo "<option value='$count'$selected>$count</option>";
			endforeach;
			?>
		</select>
		<div id="amazon_search_results" class="amazon_search_results">
		&nbsp;
		</div>
		<?php
	}
	
	public function fieldBox($post, $metabox)
	{
		$template_id = get_post_meta($post->ID, 'amazon_post_template', true);
		?>
		<div id="amazon_post_fields_container">
		<?php if($template_id) {amazon_display_fields($post->ID, $template_id, array());} ?>
		</div>
		<input type="hidden" name="amazon_current_template" value="<?php echo $curr_temp; ?>" />
		<?php
	}
	
	public function templatesBox($post, $metabox)
	{
		$templates = isset($metabox['templates']) ? $metabox['templates'] : $metabox['args']['templates']; //http://core.trac.wordpress.org/ticket/17515
		$curr_temp = get_post_meta($post->ID, 'amazon_post_template', true);
		?>
		<select name="amazon_post_template_select" style="width:85%">
			<option value="">Select One...</option>
		<?php foreach($templates as $temp) : ?>
			<option value="<?php echo $temp['id']; ?>"<?php echo $curr_temp == $temp['id'] ? ' selected="selected"' : ''; ?>><?php echo $temp['name']; ?></option>
		<?php endforeach; ?>
		</select>
		<?php
	}
	
	public function savePost($post_id)
	{
		$prefix = AmazonTools::$prefix;
		
		foreach($_POST as $key => $value)
		{
			if(strpos($key, $prefix) === 0)
				update_post_meta($post_id, $key, $value);
		}
		
		update_post_meta($post_id, 'amazon_post_template', $_POST['amazon_post_template_select']);
	}
}

$amazon_meta_box = new AmazonMetaBox();
?>