<?php
require_once('../../../wp-load.php');

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