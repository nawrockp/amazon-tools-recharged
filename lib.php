<?php
require_once('api.php');

//these are the response groups that contain useful information
//use AmazonAPI::responseGroups for all response groups
//$amazon_responseGroups = array('Offers', 'ItemAttributes', 'EditorialReview', 'Reviews', 'Images');

class AmazonLib
{
	static function getSimilar($asin)
	{
		global $amazon_locale;
		$data = array($asin => array('similar'));
		$response = amazon_fetch($data, $amazon_locale);
		
		$similar = $response[$asin]['similar'];
		$asins = @unserialize($similar);
		if(!is_array($asins))
			$asins = $similar;
			
		return $asins;
	}
	
	static function quickSearch($phrase, $locale = 'us')
	{
		$key 			= get_option('amazon_access_key');
		$secret 		= get_option('amazon_secret_key');
		$assoc			= self::assocTag();
		$method			= (get_option('amazon_method') != '') ? get_option('amazon_method') : 'sxml';
		
		$api = new AmazonAPI($key, $secret, $assoc, $locale, $method);

		$data = $api->itemSearch($phrase);
		
		//echo '<pre>'; print_r($data); echo '</pre>';
		
		$asins = array();
		$i = 0;
		if(isset($data->Items->Item))
		{
			while(isset($data->Items->Item[$i]))
			{
				$asins[] = $data->Items->Item[$i]->ASIN;
				$i++;
			}
		}
		
		return $asins;
	}
	
	static function isActive()
	{
		$key 	= get_option('amazon_access_key');
		$secret = get_option('amazon_secret_key');
		$assoc	= get_option('amazon_associate_tag');

		if($key == '' || $secret == '' || $assoc == '')
			return false;
			
		return true;
	}
	
	static function shorten($url, $len = 47)
	{
		$surl = substr($url, 0, $len);
		if(strlen($surl) != strlen($url))
			$surl .= '&hellip;';
			
		return $surl;
	}
	
	static function assocTag()
	{
		$tag			= get_option('amazon_associate_tag');
		$tag			= ($tag == '') ? 'wpamazon-20' : $tag; //associate tag is a required parameter

		return $tag;
	}
	
	static function quickLink($asin)
	{
		global $amazon_locale;
		$tag = self::assocTag();
		$tld = AmazonAPI::getTLD($amazon_locale);
		$format = 'http://www.amazon%s/dp/%s/?tag=%s';
		
		return sprintf($format, $tld, $asin, $tag);
	}
	
	static function validateNormal(&$str, $normalize = false)
	{
		$regex = '/[^a-zA-Z0-9_\-]/';
		
		if(!$normalize)
			return !preg_match($regex, $str);
		elseif(preg_match($regex, $str))
		{
			$str = preg_replace('/\s+/', '_', $str);
			$str = preg_replace($regex, '', $str);

			return false;
		}
		
		return true;
	}
	
	//List Functions	
	static function addToList($name, $asins, &$valid = null)
	{
		global $wpdb;
		$query = "REPLACE INTO {$wpdb->prefix}amazon_lists (name, asin) VALUES ";
		$append = array();
		$valid = array();
		$invalid = array();
		
		foreach($asins as $asin)
		{
			$asin = strtoupper(trim($asin));
			if($asin == '' || preg_match('/[^A-Z0-9]/', $asin))
				$invalid[] = $asin;
			else
			{
				$valid[] = $asin;
				$append[] = "('$name', '$asin')";
			}
		}
		
		if(count($append) > 0)
		{
			$query .= implode(', ', $append);
			$wpdb->query($query);
		}
		
		return $invalid;
	}
	
	static function getProductsByList($list)
	{
		global $wpdb;
		$query = "SELECT asin FROM {$wpdb->prefix}amazon_lists WHERE name = '%s';";
		
		return $wpdb->get_col( $wpdb->prepare($query, $list) );
	}
	
	static function allLists()
	{
		global $wpdb;
		$query = "SELECT name, asin FROM {$wpdb->prefix}amazon_lists ORDER BY name ASC;";
		
		$results = $wpdb->get_results($query, ARRAY_A);
		
		$last_list = '';
		$data = array();
		foreach($results as $res)
		{
			$list = $res['name'];
			$asin = $res['asin'];
			
			if(!isset($data[$list]))
				$data[$list] = array();
				
			$data[$list][] = $asin;
		}
		
		return $data;
	}
	
	static function removeListItem($list, $asin)
	{
		global $wpdb;
		$query = "DELETE FROM {$wpdb->prefix}amazon_lists WHERE name = '%s' AND asin = '%s';";
		$stmt = $wpdb->prepare($query, $list, $asin);
		
		$wpdb->query( $stmt );
	}
	
	static function drawListTableRows($name, $asins)
	{
		if(!is_array($asins))
			return;
			
		foreach($asins as $asin) :
		$url = amazon_do_shortcode("[amazon asin=\"$asin\" get=\"link\"]", true);
		$title = amazon_do_shortcode("[amazon asin=\"$asin\" get=\"title\" length=\"24\"]", true);
		if($url[0] == '%')
		{
			$class = 'bad_asin';
			$link = '<em>Could not retrieve data</em>';
		}
		else
		{
			$class = '';
			$link = "<a href='$url'>$title</a>";
		}
		?>
		<tr class="<?php echo $class; ?>">
			<td><?php echo $asin; ?></td>
			<td><?php echo $link; ?></td>
			<td class="remove_asin"><input type="button" class="button-secondary remove_asin_btn" id="remove-<?php echo $name . '-' . $asin; ?>" value="X" /></td>
		</tr>
		<?php endforeach;
	}
}

function amazon_fetch($fetch, $locale = 'us', $force_update = false)
{
	$cache = amazon_cache_fetch($fetch, $force_update);
	
	$asinMissing = array();	
	$keyMissing = array();
	foreach($fetch as $asin => $keys)
	{
		$asin = (string) $asin;
		if(!isset($cache[$asin]))
		{
			$asinMissing[] = $asin;
			$keyMissing = array_merge($keyMissing, $keys);
		}
		else
		{
			$cacheKeys = array_keys($cache[$asin]);
			foreach($keys as $k)
			{
				if(!in_array($k, $cacheKeys))
				{
					if(!in_array($asin, $asinMissing))
						$asinMissing[] = $asin;
					
					if(!is_int($k) && !in_array($k, $keyMissing))
						$keyMissing[] = $k;
				}
			}
		}
	}
	
	if(count($asinMissing) > 0)
	{
		$apidata = amazon_api_fetch($asinMissing, $keyMissing, $locale);
		$responseGroups = amazon_data_response_group($keyMissing);
		
		$items = array();
		$id = 0;
		while(isset($apidata->Items->Item[$id]))
		{
			$items[] = &$apidata->Items->Item[$id];
			$id++;
		}
		
		//echo '<pre>'; print_r($apidata->Items); echo '</pre>';
		$data = array();
		foreach($items as &$item)
		{
			$asin = (string) $item->ASIN;
			$data[$asin] = amazon_data_fetch($item, $responseGroups);
		}
		amazon_cache_insert($data);
		
		$ret = amazon_cache_fetch($fetch);
		return $ret;
	}
	
	return $cache;
}

function amazon_compress_fetch($data, &$asins, &$fetch)
{
	$asins = array();
	$fetchAll = array();
	foreach($data as $asin => $key)
	{
		$asins[] = $asin;
		$fetchAll = array_merge($fetchAll, $key);
	}
	
	$fetch = array_unique($fetchAll);
}

/**
* Fetches cached data from the amazon_cache table
*/
function amazon_cache_fetch($data, $force_update = false)
{
	global $wpdb;
	$table = $wpdb->prefix . 'amazon_cache';
	
	amazon_compress_fetch($data, $asin, $fetch);
	
	$asinStr = implode("','", $asin);
	$fetchStr = implode("','", $fetch);
	
	$query = "SELECT asin, field, value, expire FROM $table WHERE asin IN( '$asinStr' ) AND field IN ( '$fetchStr' );";
	
	return amazon_format_results($wpdb->get_results($query, ARRAY_A), $force_update);
}

/**
* Formats results from amazon_cache, also removes expired data from the results.
* Does not remove expired data from the database.
*/

function amazon_format_results($results, $force_update = false)
{
	if(!is_array($results) || count($results) < 1)
		return array();
		
	$data = array();
	foreach($results as $res)
	{
		$asin 	= (string) $res['asin'];
		$field 	= $res['field'];
		$expire	= $res['expire'];
		$value 	= $res['value'];
		
		$method = get_option('amazon_update_method');
		
		if(($method != 'init' && !$force_update) || $expire > time())
		{
			if(!isset($data[$asin]))
				$data[$asin] = array();
				
			$data[$asin][$field] = $value;
		}
		
		if($method == 'ajax' && $expire <= time())
		{
			global $amazon_tools;
			$amazon_tools->triggerAjaxUpdate();
		}
	}
	return $data;
}

function amazon_cache_insert($data)
{
	global $wpdb;
	$table = $wpdb->prefix . 'amazon_cache';
	
	$format = "REPLACE INTO $table VALUES(%s, %s, %s, %d);";
	
	foreach($data as $asin => $keys)
	{
		foreach($keys as $key => $value)
		{
			$expire = amazon_cache_expire($key);
			
			if(is_array($value) || is_object($value))
				$value = serialize($value);
			elseif($value === '' || $value === null)
				$value = amazon_blank_value($key, $asin);
			
			$stmt = $wpdb->prepare($format, $asin, $key, $value, $expire);
			
			$wpdb->query($stmt);
		}
	}
}

function amazon_blank_value($key, $asin)
{
	global $amazon_tools;
	switch($key)
	{
		case 'image' :
		case 'medium_image' :
			$value = $amazon_tools->url . '/images/no_image_med.png';
		break;
		case 'small_image' :
			$value = $amazon_tools->url . '/images/no_image_sm.png';
		break;
		case 'large_image' :
			$value = $amazon_tools->url . '/images/no_image_lg.png';
		break;
		case 'price' :
			global $amazon_locale;
			$data = array($asin => array('list_price'));
			$response = amazon_fetch($data, $amazon_locale);
			$value = $response[$asin]['list_price'];
		break;
		default :
			$value = '';
	}
	
	return $value;
}

function amazon_delete_cache()
{
	global $wpdb;
	$table = $wpdb->prefix . 'amazon_cache';
	
	$query = "DELETE FROM $table;";
	$wpdb->query($query);
}

function amazon_data_fetch(&$item, $responseGroup)
{
	require_once('dataparser.php');
	$data = array();
	
	foreach($responseGroup as $group)
	{
		$parser = new AmazonDataParser($item);
		$paths = array();
		
		switch($group)
		{
		case 'Offers':
			$base = 'Offers,Offer,OfferListing';
			$paths[] = array('key' => 'price', 			'path' => 'Price,FormattedPrice');
			$paths[] = array('key' => 'amount_saved',	'path' => 'AmountSaved,FormattedPrice');
			$paths[] = array('key' => 'availability', 	'path' => 'Availability');
			$paths[] = array('key' => 'free_shipping', 	'path' => 'IsEligibleForSuperSaverShipping', 'callback' => 'amazon_free_shipping');
		break;
		case 'ItemAttributes':
			$base = 'ItemAttributes';
			$paths[] = array('key' => 'list_price', 	'path' => 'ListPrice,FormattedPrice');
			$paths[] = array('key' => 'title', 			'path' => 'Title');
			$paths[] = array('key' => 'features', 		'path' => 'Feature', 'callback' => 'amazon_format_features');
			$paths[] = array('key' => 'genre',			'path' => 'Genre');
			$paths[] = array('key' => 'author',			'path' => 'Author', 'callback' => 'amazon_format_author');
		break;
		case 'EditorialReview':
			$base = 'EditorialReviews';
			$paths[] = array('key' => 'description', 	'path' => 'EditorialReview', 'callback' => 'amazon_product_description');
			$paths[] = array('key' => 'desc_short', 	'path' => 'EditorialReview', 'callback' => 'amazon_desc_short');
		break;
		case 'Reviews':
			$base = 'CustomerReviews';
			$paths[] = array('key' => 'reviews', 		'path' => 'IFrameURL');
			$paths[] = array('key' => 'reviews_iframe',	'path' => 'IFrameURL', 'callback' => 'amazon_reviews_iframe');
		break;
		case 'Images':
			$base = '';
			$paths[] = array('key' => 'image', 			'path' => 'MediumImage,URL');
			$paths[] = array('key' => 'small_image', 	'path' => 'SmallImage,URL');
			$paths[] = array('key' => 'medium_image', 	'path' => 'MediumImage,URL');
			$paths[] = array('key' => 'large_image', 	'path' => 'LargeImage,URL');
		case 'Small':
			$base = '';
			//$paths[] = array('key' => 'link',			'path' => 'DetailPageURL', 'callback' => 'amazon_link');
			$paths[] = array('key' => 'link',			'path' => 'DetailPageURL');
		break;
		case 'Similarities' :
			$base = 'SimilarProducts,SimilarProduct';
			$paths[] = array('key' => 'similar',		'path' => 'ASIN');
		break;
		}
		
		$groupData = $parser->parse($paths, $base);
		if(count($groupData) > 0)
			$data = array_merge($data, $groupData);
	}
	
	return $data;
}


function amazon_shortcodes_all()
{
	static $codes = array('price', 'availability', 'free_shipping',
					 'list_price', 'title', 'features',
					 'description', 'reviews', 'small_image',
					 'medium_image', 'large_image', 'reviews_iframe',
					 'link', 'amount_saved', 'desc_short', 'author', 'genre');
	return $codes;
}

function amazon_cache_expire($key)
{
	if($key == 'reviews' || $key == 'reviews_iframe')
		return time() + 79200; //the reviews iframe link expires in 24 hours
								//expire time is set to 22 hours just in case
	
	return time() + (get_option('amazon_expire') * 86400);
}

function amazon_api_fetch($asin, $fetch, $locale = 'us')
{
	$responseGroup 	= amazon_data_response_group($fetch);
	$key 			= get_option('amazon_access_key');
	$secret 		= get_option('amazon_secret_key');
	$assoc			= AmazonLib::assocTag();
	$method			= (get_option('amazon_method') != '') ? get_option('amazon_method') : 'sxml';
	
	$api = new AmazonAPI($key, $secret, $assoc, $locale, $method);

	$data = $api->itemLookup($asin, $responseGroup);
	
	return $data;
}

function amazon_data_response_group($data)
{
	$amazon_responseGroups = array(	'Offers' 			=> array('price', 'availability', 'free_shipping', 'amount_saved'),
									'ItemAttributes'	=> array('list_price', 'title', 'features', 'author', 'genre'),
									'EditorialReview'	=> array('description', 'desc_short'),
									'Reviews'			=> array('reviews', 'reviews_iframe'),
									'Images'			=> array('image', 'small_image', 'medium_image', 'large_image'),
									'Small'				=> array('link'),
									'Similarities'		=> array('similar')
								);
	
	$groups = array();
	
	foreach($data as $d)
	{
		foreach($amazon_responseGroups as $responseGroup => $elements)
		{
			if(in_array($d, $elements))
			{
				$groups[] = $responseGroup;
				unset($amazon_responseGroups[$responseGroup]); //prevent multiple instances of the same response group
			}
		}
	}
	
	return $groups;
}

//Template Functions
function amazon_get_templates()
{
	global $wpdb;
	$table = $wpdb->prefix . 'amazon_templates';
	
	$query = "SELECT id, name, type FROM $table;";
	
	return $wpdb->get_results($query, ARRAY_A);
}

function amazon_get_post_templates()
{
	global $wpdb;
	$table = $wpdb->prefix . 'amazon_templates';
	
	$query = "SELECT id, name FROM $table WHERE type = 'post';";
	
	return $wpdb->get_results($query, ARRAY_A);
}

function amazon_get_template($template)
{
	global $wpdb;
	$table = $wpdb->prefix . 'amazon_templates';
		
	if(is_numeric($template))
		$column = 'id';
	else
		$column = 'name';
	
	$query = "SELECT id, name, type, content, excerpt, css FROM $table WHERE $column = '$template';";
	
	return $wpdb->get_row($query, ARRAY_A);
}

function amazon_get_template_fields($id)
{
	global $wpdb;
	$table = $wpdb->prefix . 'amazon_template_fields';
	
	$query = "SELECT field_name, default_value FROM $table WHERE template = '$id';";
	return $wpdb->get_results($query, ARRAY_A);
}

function amazon_delete_template($id)
{
	global $wpdb;
	$table = $wpdb->prefix . 'amazon_templates';
	
	$query = "DELETE FROM $table WHERE id = '%d';";
	$stmt = $wpdb->prepare($query, $id);
	//echo $stmt;
	$wpdb->query($stmt);
}

function amazon_update_template($id, $name, $type, $content, $excerpt, $css, $fields = array())
{
	global $wpdb;
	$table = $wpdb->prefix . 'amazon_templates';
	
	$query = "UPDATE $table SET name = '%s', type= '%s', content = '%s', excerpt = '%s', css = '%s' WHERE id = '%d';";
	$stmt = $wpdb->prepare($query, $name, $type, $content, $excerpt, $css, $id);
	$wpdb->query($stmt);
	
	amazon_update_fields($id, $fields);
}

function amazon_add_template($name, $type, $content, $excerpt, $css, $fields = array())
{
	global $wpdb;
	$table = $wpdb->prefix . 'amazon_templates';
	
	$query = "INSERT INTO $table (name, type, content, excerpt, css) VALUES ('%s', '%s', '%s', '%s', '%s');";
	$stmt = $wpdb->prepare($query, $name, $type, $content, $excerpt, $css);
	$wpdb->query($stmt);
	
	$id = $wpdb->insert_id;
	amazon_update_fields($id, $fields);
	return $id;
}

function amazon_update_fields($template, $fields)
{
	if(!is_array($fields) || count($fields) == 0)
		return;
		
	global $wpdb;
	$table = $wpdb->prefix . 'amazon_template_fields';
	
	foreach($fields as $field)
	{
		$query = "REPLACE INTO $table (template, field_name, default_value) VALUES ('%d', '%s', '%s');";
		$stmt = $wpdb->prepare($query, $template, $field['field_name'], $field['default_value']);
		$wpdb->query($stmt);
	}
}

function amazon_delete_fields($template)
{
	global $wpdb;
	$table = $wpdb->prefix . 'amazon_template_fields';
	
	$query = "DELETE FROM $table WHERE template = '%d';";
	$stmt = $wpdb->prepare($query, $template);
	$wpdb->query($stmt);
}

function amazon_delete_field($template, $field_name)
{
	global $wpdb;
	$table = $wpdb->prefix . 'amazon_template_fields';
	
	$query = "DELETE FROM $table WHERE template = '%d' AND field_name = '%s';";
	$stmt = $wpdb->prepare($query, $template, $field_name);
	$wpdb->query($stmt);
}

function amazon_display_fields($post_id, $template, $override = array())
{
	$prefix = AmazonTools::$prefix;
	$fields = amazon_get_template_fields($template);
	$parsed = array();
	
	foreach($fields as $field)
	{
		$key = $field['field_name'];
		$meta_val = get_post_meta($post_id, $prefix .  $key, true);
		$parsed[$key] = ($meta_val == '') ? $field['default_value'] : $meta_val;
	}
	
	$merged = array_merge($parsed, $override);
?>
<ul style="list-style-type: none;">
	<?php if(count($merged) > 0) : ?>
	<?php foreach($merged as $key => $value) : $display_name = ucwords(str_replace('_', ' ', $key)); ?>
	<li><label for="<?php echo $key; ?>"><?php echo $display_name; ?>: </label>
		<input type="text" name="<?php echo $prefix . $key; ?>" value="<?php echo $value; ?>" /></li>
	<?php endforeach; ?>
	<?php else : ?>
	<li><em>No custom fields have been set for this template.</em></li>
	<?php endif; ?>
</ul>
<?php
}

function amazon_export_all_templates()
{
	global $wpdb;
	$table = $wpdb->prefix . 'amazon_templates';
	
	$query = "SELECT id, name, type, content, excerpt, css FROM $table;";
	$templates = $wpdb->get_results($query, ARRAY_A);
	if(!is_array($templates) || count($templates) == 0)
		return '';
		
	foreach($templates as &$template)
	{
		$template['content'] 		= stripslashes($template['content']);
		$template['css'] 			= stripslashes($template['css']);
		$template['excerpt'] 		= stripslashes($template['excerpt']);
		$template['fields'] 		= amazon_get_template_fields($template['id']);
		unset($temp['id']);
	}
	
	$templates['count'] = count($templates);
	$templates['export_version'] = AmazonTools::$version;
	
	return base64_encode(serialize($templates));
}

function amazon_export_template($id)
{
	$template = amazon_get_template($id);
	if(!$template)
		return '';
		
	unset($template['id']);
	$template['content'] 		= stripslashes($template['content']);
	$template['css'] 			= stripslashes($template['css']);
	$template['excerpt'] 		= stripslashes($template['excerpt']);
	$template['fields'] 		= amazon_get_template_fields($id);
	$template['export_version'] = AmazonTools::$version;
	
	return base64_encode(serialize($template));
}

function amazon_import_template_1_0($template)
{
	if(!is_array($template))
		return false;
	
	
	if(isset($template['count']))
	{
		unset($template['count']);
		unset($tempate['export_version']);
		$count = 0;
		foreach($template as $temp)
		{
			if(amazon_import_template_1_0($temp))
				$count++;
		}
		return $count;
	}
	else
	{
		extract($template);
		if(!isset($fields) || !is_array($fields))
			$fields = array();
			
		$id = amazon_add_template($name, $type, $content, $excerpt, $css, $fields);
		if($id)
			return true;
		
		return false;
	}
}

function amazon_import_template($content)
{
	$template = unserialize(base64_decode($content));

	return amazon_import_template_1_0($template);
}

function amazon_sample_templates()
{
	include('install_templates.php');

	amazon_import_template($templates);
}

//css functions
function amazon_css_url($ret = false)
{
	global $amazon_tools;
	
	if($amazon_tools->css_url == '')
	{
		if(!amazon_create_css_file())
		{
			$url = wp_upload_dir();
			$url = $url['baseurl'] . '/amazon-tools/amazon.css';
		}
	}
	
	if(!isset($url))
		$url = $amazon_tools->css_url;
	
	if($ret)
		return $url;
		
	echo $url;
}

function amazon_css_dir($ret = false)
{
	global $amazon_tools;
	
	if($amazon_tools->css_dir == '')
	{
		if(!amazon_create_css_file())
		{
			$dir = wp_upload_dir();
			$dir = $dir['basedir'] . '/amazon-tools';
		}
	}
	elseif(!file_exists($amazon_tools->css_dir))
		amazon_create_css_file();
	
	if(!isset($dir))
		$dir = $amazon_tools->css_dir;
	
	if($ret)
		return $dir;
		
	echo $dir;
}

function amazon_create_css_file()
{
	$upload = wp_upload_dir();
	$url = $upload['baseurl'] . '/amazon-tools/amazon.css';
	$dir = $upload['basedir'] . '/amazon-tools/amazon.css';
	if(is_writable($upload['basedir']))
	{
		if( (file_exists($upload['basedir'] . '/amazon-tools') || @mkdir($upload['basedir'] . '/amazon-tools'))
			 && @fopen($dir, 'a') )
		{
			global $amazon_tools;
			
			$amazon_tools->css_url = $url;
			$amazon_tools->css_dir = $dir;
			
			update_option('amazon_global_css', $url);
			update_option('amazon_global_css_dir', $dir);
			
			return true;
		}
	}
	
	return false;
}

function amazon_can_write_css()
{
	return is_writable(amazon_css_dir(true));
}

//Callback functions
function amazon_format_author($author)
{
	if(is_array($author))
		$author = implode(', ', $author);
		
	return $author;
}
function amazon_format_features($features)
{
	if(!is_array($features) || count($features) < 1)
		return;
		
	ob_start();
	?>
<ul class="amazon_features">
<?php foreach($features as $f) : ?>
	<li><?php echo $f; ?></li>
<?php endforeach; ?>
</ul>
	<?php
	return ob_get_clean();
}

function amazon_reviews_iframe($url)
{
	return '<iframe scrolling="no" width="100%" frameborder="0" class="amazon_reviews_iframe" src="' . $url . '"></iframe>';
}

function amazon_free_shipping($fs)
{
	if($fs == 1)
		return 'Available for free shippping';
		
	return 'Not available for free shipping';
}

function amazon_product_description($desc)
{
	if(isset($desc['Content']))
		return $desc['Content'];
	elseif(isset($desc[0]))
		return $desc[0]['Content'];
		
	return '';
}

function amazon_desc_short($desc)
{
	$desc = amazon_product_description($desc);
	$len = strlen($desc);
	$maxlen = get_option('amazon_description_length');
	if($maxlen == '')
		$maxlen = 500;
	
	if($len > $maxlen)
		$desc = substr($desc, 0, $maxlen) . '&#0133;';
		
	return $desc;
}

function amazon_link($link)
{
	return urldecode($link);
}

//Misc Functions
function amazon_admin_url($params = array(), $keep_get = true, $unset = null)
{
	if($keep_get)
	{
		$params = array_merge($_GET, $params);
		if(is_array($unset))
		{
			foreach($unset as $key)
				unset($params[$key]);
		}
	}
		
	$query = '?' . http_build_query($params);
	$admin_url = get_option('siteurl') . '/wp-admin/admin.php';
	
	return $admin_url . $query;
}

function amazon_admin_footer()
{
	global $amazon_tools;
?>
<div class="amazon_footer">
Amazon Tools Version <a href="<?php echo AmazonTools::$version_url; ?>" target="_blank"><?php echo AmazonTools::$version; echo AmazonTools::$is_premium ? ' Premium' : ' Basic'; ?></a><br />
This software is not supported or endorsed by Amazon.com<br />
<a href="http://tinsology.net/plugins/amazon-tools/">Documentation</a> <a href="http://tinsology.net/plugins/amazon-tools/" class="new_window"></a> | 
<a href="http://forums.tinsology.net/index.php">Forum</a> <a href="http://forums.tinsology.net/index.php" class="new_window"></a> <br /><br />
<?php if(!AmazonTools::$is_premium) : ?>
<a href="<?php echo AmazonTools::$premium_url; ?>" target="_blank"><img src="<?php echo $amazon_tools->url; ?>/menus/img/upgrade.png" width="728" height="90" style="border: 2px solid #000;" /></a>
<?php endif; ?>
</div>
<?php
}

function amazon_do_shortcode($shortcode, $ret = false)
{
	global $amazon_tools;
	
	$content = do_shortcode($shortcode);
	$content = $amazon_tools->bufferData($content);
	
	if($ret)
		return $content;
		
	echo $content;
}

function amazon_start()
{
	ob_start();
}

function amazon_end($ret = false)
{
	$content = @ob_get_clean();
	return amazon_do_shortcode($content, $ret);
}

function amazon_caching($switch)
{
	//TODO implement cache switch
	global $amazon_enable_cache;
	$amazon_enable_cache = (bool) $switch;
}
?>