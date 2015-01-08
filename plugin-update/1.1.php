<?php
$amazon_plugin_update->enqueue('amazon_update_1_1', '1');
	
function amazon_update_1_1()
{
	global $wpdb;
	$prefix = $wpdb->prefix;
	$drop_suggest = "DROP TABLE {$prefix}amazon_suggest;";
	
	$wpdb->query($drop_suggest);
}
?>