<?php
$amazon_plugin_update->enqueue('amazon_update_1_5', '5');
	
function amazon_update_1_5()
{
	add_option('amazon_getting_started', 1);
	
	global $wpdb;
	$lists = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}amazon_lists (
					name VARCHAR(32),
					asin VARCHAR(10),
					PRIMARY KEY(name, asin)
				);";
				
	$wpdb->query($lists);
}
?>