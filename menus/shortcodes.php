<?php
if($_SERVER['REQUEST_METHOD'] == 'POST')
		include('shortcodes_post.php');
		
$shortcodes = amazon_shortcodes_all();
?>

<div class="wrap">
<h2>Amazon Shortcodes</h2>

<?php if(isset($shortcode_message)) : ?>
<div class="updated"><p><strong><?php echo $shortcode_message; ?></strong></p></div> <br />
<?php endif; ?>

<form method="post" action="">

<strong>ASINs (separate with commas)</strong>
<input type="text" name="shortcode_asin" value="<?php echo isset($_POST['shortcode_asin']) ? $_POST['shortcode_asin'] : ''; ?>" />

<table width="550">
<?php
for($i = 0; $i < count($shortcodes); $i++)
{
	$code = $shortcodes[$i];
	if($i % 4 == 0)
		echo '<tr>';
	?>
	<td>
	<input type="checkbox" 
		name="shortcode_codes[]"
		value="<?php echo $code; ?>"
		<?php echo is_array($_POST['shortcode_codes']) && in_array($code, $_POST['shortcode_codes']) ? 'checked="checked"' : ''; ?> />
	<?php echo $code; ?>
	</td>
	<?php		
	if($i % 4 == 3)
		echo '</tr>';
}

if($i % 4 != 3)
	echo '</tr>';
?>
</table>

<p class="submit">
<input type="submit" name="shortcodes_submit" class="button-primary" value="<?php _e('Generate') ?>" />
</p>

</form>

<?php if(isset($shortcode_results)) : ?>
<strong>Results</strong> <br /><br />

<?php foreach($shortcode_results as $asin => $codes) : ?>

<em><?php echo $asin; ?></em> <br />
<textarea cols="60" rows="<?php echo count($_POST['shortcode_codes']); ?>"><?php echo $codes; ?></textarea><br /><br />

<?php endforeach; ?>

<?php endif; ?>
</div>