<?php
if($_POST['shortcode_asin'] == '')
	$shortcode_message = 'You must set at least one ASIN';
if(!is_array($_POST['shortcode_codes']) || count($_POST['shortcode_codes']) < 1)
	$shortcode_message = 'You must select at least on element';
	
if(!isset($shortcode_message))
{
	$asin = explode(',', $_POST['shortcode_asin']);
	$codes = $_POST['shortcode_codes'];
	
	$shortcode_results = array();
	foreach($asin as $a)
	{
		$curr = '';
		foreach($codes as $code)
		{
			$curr .= '[amazon asin="' . $a . '" fetch="' . $code . '"]' . "\n";
		}
		$curr = substr($curr, 0, -1);
		
		$shortcode_results[$a] = $curr;
	}
	
	$shortcode_message = 'Shortcodes Generated';
}
?>