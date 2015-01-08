<?php
$amazon_plugin_update->enqueue('amazon_update_1_3', '3');
	
function amazon_update_1_3()
{
	add_option('amazon_post_types', array('post', 'page'));
}
?>