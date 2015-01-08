<?php
class AmazonPost
{
	static function newList(&$message)
	{
		$list_name = strtolower($_POST['new_list']);
		$asins = $_POST['new_list_asins'];
		
		AmazonLib::validateNormal($list_name, true);
		if($list_name == '')
		{
			$message = 'Invalid list name! List names may only consist of lowercase letters, digits, underscores ( _ ) and hyphens ( - ).';
			return;
		}
		
		$asins = explode(',', $asins);
		if(!is_array($asins) || count($asins) == 0)
		{
			$message = 'You must specify at least one product ASIN.';
			return;
		}
		
		$invalid = AmazonLib::addToList($list_name, $asins);
	}
}
if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	$message;
	if(isset($_POST['new_list']))
	{
		AmazonPost::newList($message);
	}
}

global $amazon_meta_box, $amazon_tools;
$lists = AmazonLib::allLists();
?>
<div class="wrap amazon_lists">

<div class="menu_icon icon32"><br /></div>
<h2>Lists</h2>

<?php if(isset($message) && $message != '') : ?>
<div class="updated"><p><strong><?php echo $message; ?></strong></p></div> <br />
<?php endif; ?>

<div id="amazon_tools_quick_search" class="postbox" >
<h3>Quick Search</h3>
<div class="inside">
<?php
$amazon_meta_box->quickSearchBox();
?>
</div>
</div>

<?php foreach($lists as $name => $asins) : ?>

<div class="postbox list" id="list-<?php echo $name; ?>">
	
	<h3><?php echo $name; ?></h3>
	
		
	<table class="widefat" id="new_list">
		<?php AmazonLib::drawListTableRows($name, $asins); ?>
	</table>
	
	<div class="add_asins">
	<strong>ASINs</strong> <em>(separate with commas)</em>
	<textarea></textarea>
	<a class="delete_list">Delete list</a>
	<input type="submit" class="button-primary" name="add_product" value="Add Products" />
	</div>
	
</div>

<?php endforeach; ?>

<form action="" method="post" id="new_list_form">
<div class="postbox list">
	
	<h3><input type="text" name="new_list" value="New List" /></h3>
	
	<div class="add_asins">
	<strong>ASINs</strong> <em>(separate with commas)</em>
	<textarea name="new_list_asins"></textarea>
	<input type="submit" class="button-primary" name="create_list" value="Create List" />
	</div>
	
</div>
</form>

<br style="clear:both;" />

<h3>Using Lists</h3>
Lists allow you to organize products that you can then display on your blog using the <code>amazon</code> shortcode. If you create a list called books
you can display a template (called 'My Template' in this example) for each book with the following shortcode:
<pre>
[amazon list="books" template="My Template"]
</pre>

Another way you can use lists is to organize the products you want to display in ads. The following shortcode will display one product from the list 'books'
using the 'Simple Ad Unit' template:
<pre>
[amazon list="books" template="Simple Ad Unit" random="1"]
</pre>
<br />
<?php amazon_admin_footer(); ?>

</div>