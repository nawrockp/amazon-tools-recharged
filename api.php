<?php
/**
* Developed by Mathew Tinsley (tinsley@tinsology.net)
* http://tinsology.net
*
* Version: 1.3 (Aug 10 2011)
* API Version: 2011-08-01
*
*/
class AmazonAPI {
	
	private $accessKey;
	private $associateTag;
	private $secretKey;
	
	private $method;
	private $locale;
	
	private $protocol	= 'http://';
	private $baseurl 	= 'webservices.amazon';
	private $urlpath	= '/onca/xml';
	private $apiversion	= '2011-08-01';
	private $hashalgo	= 'sha256';
	private $service	= 'AWSECommerceService';
	
	public function __construct($accessKey, $secretKey, $associateTag = '', $locale = 'us', $method = 'sxml')
	{
		$this->accessKey = $accessKey;
		$this->secretKey = $secretKey;
		$this->associateTag = $associateTag;
		$this->locale = strtolower($locale);
		$this->method = $method;
		
		$this->baseurl .= self::getTLD($locale);
	}
	
	public function itemLookup($asin, $responseGroup)
	{
		if(is_array($asin))
			$asin = implode('%2C', $asin);
		else
			$asin = str_replace(',', '%2C', $asin);
			
		if(is_array($responseGroup))
			$responseGroup = implode('%2C', $responseGroup);
		else
			$responseGroup = str_replace(',', '%2C', $responseGroup);
			
		$request = array();
		$request['AWSAccessKeyId'] = $this->accessKey;
		$request['AssociateTag'] = $this->associateTag;
		$request['ItemId'] = $asin;
		$request['Operation'] = 'ItemLookup';
		$request['ResponseGroup'] = $responseGroup;
		$request['Service'] = $this->service;
			
		$signed = $this->sign($request);
		
		$complete = $this->protocol . $this->baseurl . $this->urlpath . '?' . $signed;
		
		$xml = $this->request($complete);
		
		return $xml;
	}
	
	public function similarityLookup($asin)
	{
		if(is_array($asin))
			$asin = implode('%2C', $asin);
		else
			$asin = str_replace(',', '%2C', $asin);
			
		$request = array();
		$request['AWSAccessKeyId']	= $this->accessKey;
		$request['ItemId']			= $asin;
		$request['Operation']		= 'SimilarityLookup';
		$request['Service']			= $this->service;
		
		$signed = $this->sign($request);
		
		$complete = $this->protocol . $this->baseurl . $this->urlpath . '?' . $signed;
		
		$xml = $this->request($complete);
		
		return $xml;
	}
	
	public function itemSearch($phrase, $index = 'All')
	{
		$request = array();
		
		$request['AWSAccessKeyId']		= $this->accessKey;
		$request['AssociateTag'] 		= $this->associateTag;
		$request['Keywords']			= str_replace(' ', '%20', $phrase);
		$request['Operation']			= 'ItemSearch';
		$request['SearchIndex']			= $index;
		$request['Service']			= $this->service;
		
		$signed = $this->sign($request);
		
		$complete = $this->protocol . $this->baseurl . $this->urlpath . '?' . $signed;
		
		$xml = $this->request($complete);
		
		return $xml;
	}
	
	/**
	* Calls the correct request method depnding on the method selected in
	* the constructor (default: sxml)
	*/
	private function request($request)
	{
		$func = $this->method . 'Request';
		return call_user_func(array($this, $func), $request);
	}
	
	/**
	* Generates the signature for the request
	* Also adds the timestamp and version parameters
	* Request should be an array of the form parameters => value
	* Returns the signed request as a string
	*/
	private function sign($request)
	{
		$timestamp = gmdate("Y-m-d\TH:i:s\Z"); 
		$timestamp = str_replace(':', '%3A', $timestamp);
		
		$request['Timestamp'] = $timestamp;
		$request['Version'] = $this->apiversion;
		
		ksort($request, SORT_STRING);
		
		$prepend = "GET\n{$this->baseurl}\n{$this->urlpath}\n";
		
		$requestStr = $this->build_query($request);

		$prependStr = $prepend . $requestStr;
		
		$signature = hash_hmac($this->hashalgo, $prependStr, $this->secretKey, true);
		$signature = base64_encode($signature);
		$signature = str_replace('+', '%2B', $signature);
		$signature = str_replace('=', '%3D', $signature);
		
		$requestStr .= "&Signature=$signature";
		
		return $requestStr;
	}
	
	/**
	* When I wrote the API originally I used http_build_query to construct
	* the query portion of the request uri. The url encoding caused the signature
	* in the request not to match the signautre generated by the server. This
	* method duplicates the functionality of http_build_query, without encoding
	* the data. Encoding is done manually for the signature and timestamp.
	*/
	private function build_query($data)
	{
		$query = '';
		foreach($data as $param => $value)
		{
			$query .= "$param=$value&";
		}
		
		return substr($query, 0, -1);
	}
	
	/**
	* I'm almost certain that under any condition that the simplexml
	* method would fail, this method would fail also. Here it is anyways.
	*/
	private function fgcRequest($request)
	{
		$response = file_get_contents($request);
		
		return parseResponse($response);
	}
	
	/**
	* CURL can be used in cases where allow_url_fopen is disabled in
	* the php.ini
	*/
	private function curlRequest($request)
	{
		$session = curl_init($request);
		curl_setopt($session, CURLOPT_HEADER, false);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($session);
		curl_close($session);
		
		return parseResponse($response);
	}
	
	/**
	* This is the default method. Note that it requires simplexml to be
	* enabled and allow_url_fopen to be enabled. Simplexml is enabed by
	* default as of PHP 5.1.2
	*/
	private function sxmlRequest($request)
	{
		return @simplexml_load_file($request);
		//no need to parse the response
	}
	
	/**
	* Processes the response when simple_xml_load_file isn't used (ie fgcRequest
	* and curlRequest).
	*/
	private function parseResponse($response)
	{
		return simplexml_load_string($response);
	}
	
	/**
	* Used to get the tld corresponding to the locale
	*/
	public static function getTLD($locale)
	{
		static $locales = array(	'us' => '.com',
									'uk' => '.co.uk',
									'ca' => '.ca',
									'de' => '.de',
									'fr' => '.fr',
									'it' => '.it',
									'jp' => '.jp'
								);
						
		return $locales[$locale];
	}
	
	/**
	* These are the operations supported by the current version of the api.
	* 1 indicates that the operation is implemented here, 0 indicates that
	* it is not.
	*/
	public static $operations = array(	'ItemLookup' 			=> 1,
										'ItemSearch' 			=> 1,
										'BrowseNodeLookup'		=> 0,
										'CartAdd'				=> 0,
										'CartClear'				=> 0,
										'CartCreate'			=> 0,
										'CartGet'				=> 0,
										'CartModify'			=> 0,
										'SellerListingLookup'	=> 0,
										'SellerListingSearch'	=> 0,
										'SellerLookup'			=> 0,
										'SimilarityLookup'		=> 1
									);
	
	/**
	* These are the locales supported by the current version of the API
	*/
	public static $locales = array('US', 'UK', 'CA', 'DE', 'FR', 'IT', 'JP');
	
	/**
	* These are all of the response groups in the current version of
	* the API
	*/
	public static $responseGroups = array(	'Accessories',
											'AlternateVersions',
											'BrowseNodes',
											'Collections',
											'EditorialReview',
											'Images',
											'ItemAttributes',
											'ItemIds',
											'Large',
											'ListmaniaLists',
											'Medium',
											'MerchantItemAttributes',
											'OfferFull',
											'OfferListings',
											'OfferSummary',
											'Offers',
											'PromotionDetails',
											'PromotionSummary',
											'PromotionalTag',
											'RelatedItems',
											'Request',
											'Reviews',
											'SalesRank',
											'SearchBins',
											'SearchInside',
											'ShippingCharges',
											'ShippingOptions',
											'Similarities',
											'Small',
											'Subjects',
											'Tags',
											'TagsSummary',
											'Tracks',
											'VariationImages',
											'VariationMatrix',
											'VariationMinimum',
											'VariationOffers',
											'VariationSummary',
											'Variations'
										);
										//Contact: tinsley@tinsology.net
}
?>