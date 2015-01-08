<?php
global $amazon_tools;
class AmazonPost
{
	public static function prepareFields($id = null)
	{
		$field_names = array();
		$fields = array();
		
		$i = 0;
		while(isset($_POST['template_field_names_' . $i]))
		{
			$field_name = strtolower(str_replace(' ', '_', $_POST['template_field_names_' . $i]));
			if($field_name != '')
			{
				$field_names[] = $field_name;
				$fields[] = array(	'field_name' 	=> $field_name, 
									'default_value'	=> $_POST['template_field_defaults_' . $i]);
			}
			$i++;
		}
		
		if($id)
		{
			$curr_fields = amazon_get_template_fields($id);
			foreach($curr_fields as $key => $val)
			{
				if(!in_array($val['field_name'], $field_names))
					amazon_delete_field($id, $val['field_name']);
			}
		}
		
		return $fields;
	}
}
if(is_numeric($_POST['template_name']))
{
	$templates_message = 'Invalid Template Name';
}
elseif($_POST['template_submit'] == 'Save Template')
{
	if(isset($_POST['import_template']))
	{
		$count = amazon_import_template($_POST['import_template']);
		if($count === true || $count == 1)
			$templates_message = 'Template Imported';
		elseif($count > 1)
			$templates_message = "$count Templates Imported";
		else
			$templates_message = 'Import Failed!';
	}
	else
	{
		$name 		= $_POST['template_name'];
		$content 	= $_POST['template_content'];
		$css		= $_POST['template_css'];
		$type		= isset($_POST['template_excerpt']) ? 'post' : 'shortcode';
		$excerpt 	= isset($_POST['template_excerpt']) ? $_POST['template_excerpt'] : '';
		$fields 	= AmazonPost::prepareFields();
		
		$id = amazon_add_template($name, $type, $content, $excerpt, $css, $fields);
		$templates_message = 'Template Added';
		
		if($id)
			$_GET['edit'] = $id;
	}
}
elseif($_POST['template_submit'] == 'Update Template')
{
	if(isset($_POST['edit_css']) && $_POST['edit_css'] == 1)
	{
		$css = $_POST['template_content'];
		if(amazon_can_write_css())
		{
			$dir = amazon_css_dir(true);

			if(file_put_contents($dir, $css) !== false)
			{
				$templates_message = 'CSS Updated';
				update_option('amazon_css_version', time());
			}
			else
				$templates_message = 'Failed to write to CSS file';
		}
	}
	else
	{
		$name 		= $_POST['template_name'];
		$content	= $_POST['template_content'];
		$excerpt	= $_POST['template_excerpt'];
		$id		 	= $_POST['template_id'];
		$css		= $_POST['template_css'];
		$type		= isset($_POST['template_excerpt']) ? 'post' : 'shortcode';
		$excerpt 	= isset($_POST['template_excerpt']) ? $_POST['template_excerpt'] : '';
		$fields 	= AmazonPost::prepareFields($id);
		
		amazon_update_template($id, $name, $type, $content, $excerpt, $css, $fields);
		$templates_message = 'Template Updated';
	}
}
?>