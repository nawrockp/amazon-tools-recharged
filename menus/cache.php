<?php 
global $amazon_tools;

class AmazonCache {
	
	public static function getCache()
	{
		global $wpdb;
		$query = "SELECT asin, field, value, expire FROM {$wpdb->prefix}amazon_cache ORDER BY asin";
		return $wpdb->get_results($query, ARRAY_A);
	}
	
	public static function formatExpireTime($expire)
	{
		$diff = $expire - time();
		$days = (int) ($diff / 60 / 60 / 24);
		
		$diff = $diff - ($days * 60 * 60 * 24);
		$hours = (int) ($diff / 60 / 60);
		
		$diff = $diff - ($hours * 60 * 60);
		$minutes = (int) ($diff / 60);
		
		$diff = $diff - ($minutes * 60);
		$seconds = $diff;
		
		return "$days days $hours hours $minutes minutes";
	}
	
	public static function delete($asin, $field = '')
	{
		if($asin == '')
			return;
		
		global $wpdb;
		
		$wpdb->escape_by_ref($asin);
		
		$where = "WHERE asin='$asin'";
		
		if($field != '')
		{
			$wpdb->escape_by_ref($field);
			$where .= " AND field='$field'";
		}
		
		$query = "DELETE FROM {$wpdb->prefix}amazon_cache $where";
		
		return $wpdb->query($query);
	}
}

if(isset($_GET['action']) && $_GET['action'] == 'delete')
{
	$delete_count = AmazonCache::delete($_GET['asin'], $_GET['field']);
	$row_str = ($delete_count > 1) ? 'rows' : 'row';
	if($delete_count)
		$message = "$delete_count $row_str deleted.";
}

if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	if(isset($_POST['amazon_cache_delete']))
	{
		//delete cache
		amazon_delete_cache();
		$message = 'Cache Emptied';
	}
}

$cache = AmazonCache::getCache();
$last_asin = '';
?>
<div class="wrap">

<div class="menu_icon icon32"><br /></div>
<h2>Cache</h2>

<?php if(isset($message)) : ?>
<div class="updated"><p><strong><?php echo $message; ?></strong></p></div> <br />
<?php endif; ?>
<br />

<table id="pending-posts" class="widefat">
<thead>
	<tr>
		<th style="width: 24px;"></th>
		<th style="width: 12%;">ASIN</th>
		<th style="width: 15%;">Entry Name</th>
		<th style="width: 45%;">Entry Value</th>
		<th style="width: 15%;">Expires In</th>
		<th style="width: 10%;">Delete</th>
	</tr>
</thead>
<?php if(!is_array($cache) || count($cache) < 1) : ?>
	<tr>
		<td colspan="6" style="text-align:center;"><em>Cache is empty</em></td>
	</tr>
<?php else : ?>
<?php foreach($cache as $item) : ?>
<?php if($last_asin != $item['asin']) : ?>

	<tr class="cache_section">
		<th class="cache_expand" id="<?php echo $item['asin']; ?>"></th>
		<th><?php echo $item['asin']; ?></th>
		<th></th>
		<th></th>
		<th></th>
		<th><a href="<?php echo amazon_admin_url(array('action' => 'delete', 'asin' => $item['asin']), true, array('field')); ?>">Delete All</a></th>
	</tr>
<?php $last_asin = $item['asin']; ?>

<?php endif; ?>
	<tr class="cache_sub <?php echo $item['asin']; ?>">
		<td></td>
		<td></td>
		<td><?php echo $item['field']; ?></td>
	<?php if(strlen($item['value']) > 150) : ?>
		<td><textarea readonly="readonly" style="height:42px;width:90%;"><?php echo $item['value']; ?></textarea></td>
	<?php else : ?>
		<td><pre><?php echo $item['value']; ?></pre></td>
	<?php endif; ?>
		<td><?php echo AmazonCache::formatExpireTime($item['expire']); ?></td>
		<td><a href="<?php echo amazon_admin_url(array('action' => 'delete', 'asin' => $item['asin'], 'field' => $item['field'])); ?>">Delete</a></td>
	</tr>

<?php endforeach; ?>
<?php endif; ?>
</table>
<br />
<form method="post" action="">
<input type="submit" name="amazon_cache_delete" class="button-primary" value="Empty Cache" />
</form>
<br /><br />

<?php amazon_admin_footer(); ?>

</div>