<?php
$amazon_plugin_update->enqueue('amazon_update_1_2', '2');
	
function amazon_update_1_2()
{
	add_option('amazon_excerpt_non_single', 1);
}
?>