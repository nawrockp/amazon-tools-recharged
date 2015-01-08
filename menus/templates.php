<?php
if($_SERVER['REQUEST_METHOD'] == 'POST')
	include('templates_post.php');

if(isset($_GET['edit']))
{
	if($_GET['edit'] == 'css')
	{
		$css = @file_get_contents(amazon_css_dir(true));
	}
	else
		$edit_temp = amazon_get_template( (int) $_GET['edit'] );
}
elseif(isset($_GET['delete']))
{
	amazon_delete_template( (int) $_GET['delete'] );
	$templates_message = 'Template Deleted';
}

$templates = amazon_get_templates();
$shortcodes = amazon_shortcodes_all();

//determine type
if(isset($_GET['new']))
	$type = $_GET['new'];
elseif(isset($edit_temp))
	$type = $edit_temp['type'];
elseif(isset($_GET['edit']) && $_GET['edit'] == 'css')
	$type = 'css';

//$edit_css = isset($_GET['edit']) && $_GET['edit'] == 'css';
$can_save = ($type == 'css' && !amazon_can_write_css()) || isset($_GET['export']) ? false : true;

if($type == 'post' && isset($_GET['edit']))
{
	$fields = amazon_get_template_fields( (int) $_GET['edit'] );
}
global $amazon_tools;
?>

<div class="wrap">

<div class="menu_icon icon32"><br /></div>
<h2>Templates</h2>

<?php if(isset($templates_message)) : ?>
<div class="updated"><p><strong><?php echo $templates_message; ?></strong></p></div>
<?php endif; ?>

<br />

<table class="widefat templates_table">
<thead>
	<tr>
		<th>ID</th>
		<th>Name</th>
		<th>Template Type</th>
		<th>Edit</th>
		<th>Export</th>
		<th>Delete</th>
	</tr>
</thead>

<?php if(count($templates) < 1) : ?>
<tr>
	<td colspan="6" style="text-align: center"><em>No templates</em><br /><em><a href="http://forums.tinsology.net/viewtopic.php?f=9&t=45">Click Here</a> to get a few templates to get you started!</em></td>
</tr>
<?php else : foreach($templates as $temp) : ?>
<tr>
	<td><?php echo $temp['id']; ?></td>
	<td><?php echo $temp['name']; ?></td>
	<td><?php echo $temp['type']; ?></td>
	<td><a href="<?php echo amazon_admin_url(array('edit' => $temp['id']), true, array('delete', 'new', 'export')); ?>">Edit</a></td>
	<td><a href="<?php echo amazon_admin_url(array('export' => $temp['id']), true, array('delete', 'new', 'edit')); ?>">Export</a></td>
	<td><a href="<?php echo amazon_admin_url(array('delete' => $temp['id']), true, array('edit', 'new', 'export')); ?>">Delete</a></td>
</tr>
<?php endforeach; endif; ?>
	<tr>
		<th></th>
		<th>Global CSS</th>
		<th></th>
		<th><a href="<?php echo amazon_admin_url(array('edit' => 'css'), true, array('delete', 'new', 'export')); ?>">Edit</a></th>
		<th></th>
		<th></th>
	</tr>
</table>

<form method="post" action="<?php  echo amazon_admin_url(array(), true, array('delete', 'new', 'export')); ?>">

<div class="template_sidebar">

<!--Add new-->
<ul>
<li><a class="button-secondary preview" href="<?php echo amazon_admin_url(array('new' => 'shortcode'), true, array('edit', 'delete', 'export')); ?>">New Shortcode Template</a></li>
<li><a class="button-secondary preview" href="<?php echo amazon_admin_url(array('new' => 'post'), true, array('edit', 'delete', 'export')); ?>">New Post Template</a></li>
<li><a class="button-secondary preview" href="<?php echo amazon_admin_url(array('new' => 'import'), true, array('edit', 'delete', 'export')); ?>">Import Template</a></li>
<li><a class="button-secondary preview" href="<?php echo amazon_admin_url(array('export' => 'all'), true, array('edit', 'delete', 'new')); ?>">Export All Templates</a></li>
<!--End add new-->
<li style="margin-top: 20px;">
<?php if($can_save && isset($type)) : ?>
<input type="submit" 
	class="button-primary preview" 
	name="template_submit" 
	value="<?php echo isset($edit_temp) || $type == 'css' ? 'Update Template' : 'Save Template'; ?>" />
<?php endif; ?>
</li>
</ul>
</div>

<br style="clear:both" /><br />

<?php if($type == 'css' && !$can_save) : ?>
<div class="updated"><strong>
	<?php if($amazon_tools->css_dir == '') : ?>
	Warning: Amazon Tools cannot create the CSS file. You must either make the following directory writable by PHP, or upload a file called amazon.css and make it writable. File path:
	<?php else : ?>
	Warning: Amazon Tools cannot write to the CSS file. Make the file writable if you wish to edit it through the plugin. File path:
	<?php endif; ?>
</strong> <br /> <?php amazon_css_dir(); ?> </div>
<?php endif; ?>

<?php if($type == 'css') : ?>

	<strong>Content</strong> <br />
	<div class="amazon_tab_override">
	<textarea<?php if(!$can_save) {echo ' readonly="readonly"';} ?> name="template_content"><?php echo (isset($css)) ? $css : ''; ?></textarea>
	</div>
	<br style="clear:both;" />

	<input type="hidden" name="edit_css" value="1" />

<?php elseif($type == 'post') : ?>

	<strong>Template Name</strong> <br />
	<input type="text" style="width:240px;" name="template_name" value="<?php echo (isset($edit_temp)) ? $edit_temp['name'] : ''; ?>" />

	<br /><br />

	<strong>Content</strong> <br />
	<div class="amazon_tab_override">
	<textarea name="template_content"><?php echo (isset($edit_temp)) ? stripslashes($edit_temp['content']) : ''; ?></textarea>
	</div>
	<br style="clear:both;" /><br />
	
	<div id="post_fields">
	
	<?php if(count($fields) > 0) : $i = 0; foreach($fields as $field) : ?>
	
	<strong>Field Name: </strong> <input type="text" value="<?php echo $field['field_name']; ?>" name="template_field_names_<?php echo $i; ?>" /> 
	<strong>Default Value: </strong> <input type="text" value="<?php echo $field['default_value']; ?>" name="template_field_defaults_<?php echo $i; ?>" /> 
	<br /><br />
	
	<?php $i++; endforeach; ?>
	<input type="hidden" value="<?php echo $i; ?>" name="template_field_index" />
	<?php else : ?>
	
	<strong>Field Name: </strong> <input type="text" name="template_field_names_0" /> 
	<strong>Default Value: </strong> <input type="text" name="template_field_defaults_0" />
	<br /><br />
	<input type="hidden" value="1" name="template_field_index" />
	
	<?php endif; ?>
	
	</div>
	
	<input type="button" class="button-secondary" id="add_field" value="Add Field" />
	<br /><br />
	
	<strong>Excerpt</strong> <br />
	<div class="amazon_tab_override">
	<textarea name="template_excerpt"><?php echo (isset($edit_temp)) ? stripslashes($edit_temp['excerpt']) : ''; ?></textarea>
	</div>
	<br style="clear:both;" /><br />
	
	<strong>Additional CSS</strong> <br />
	<div class="amazon_tab_override">
	<textarea name="template_css"><?php echo (isset($edit_temp)) ? stripslashes($edit_temp['css']) : ''; ?></textarea>
	</div>
	<br style="clear:both;" />

	<?php if(isset($edit_temp)) : ?>
	<input type="hidden" name="template_id" value="<?php echo $edit_temp['id']; ?>" />
	<?php endif; ?>

<?php elseif($type == 'shortcode') : ?>

	<strong>Template Name</strong> <br />
	<input type="text" style="width:240px;" name="template_name" value="<?php echo (isset($edit_temp)) ? $edit_temp['name'] : ''; ?>" />

	<br /><br />

	<strong>Content</strong> <br />
	<div class="amazon_tab_override">
	<textarea name="template_content"><?php echo (isset($edit_temp)) ? stripslashes($edit_temp['content']) : ''; ?></textarea>
	</div>
	<br style="clear:both;" /><br />

	<strong>Additional CSS</strong> <br />
	<div class="amazon_tab_override">
	<textarea name="template_css"><?php echo (isset($edit_temp)) ? stripslashes($edit_temp['css']) : ''; ?></textarea>
	</div>
	<br style="clear:both;" />

	<?php if(isset($edit_temp)) : ?>
	<input type="hidden" name="template_id" value="<?php echo $edit_temp['id']; ?>" />
	<?php endif; ?>

<?php elseif($type == 'import') : ?>

	<strong>Import</strong><br />
	<textarea class="template_port" name="import_template"></textarea>
	<br style="clear:both;" />
	
	
<?php elseif(isset($_GET['export'])) : ?>

	<strong>Template Data</strong><br />
	<?php if($_GET['export'] == 'all') : ?>
	<textarea readonly="readonly" class="template_port"><?php echo amazon_export_all_templates(); ?></textarea>
	<?php elseif(is_numeric($_GET['export'])) : ?>
	<textarea readonly="readonly" class="template_port"><?php echo amazon_export_template((int) $_GET['export']); ?></textarea>
	<?php endif; ?>
	<br style="clear:both;" />
	
<?php endif; ?>
</form>
<br /><br />

For information about using and creating templates, visit the <a href="http://forums.tinsology.net/index.php">Amazon Tools Forum</a>
<a href="http://forums.tinsology.net/index.php" class="new_window"></a> at Tinsology.net
<br /><br />
<strong>Available Elements</strong> <br /><br />
You may pass any of the following elements as the 'get' parameter in your shortcode. More elements may be added in future versions <br /><br />
<em><?php echo implode(', ', $shortcodes); ?></em> <br /><br />
Example: 
<code>
	[amazon asin="0307406105" get="list_price"]
</code>
<br /><br />

<?php amazon_admin_footer(); ?>
</div>