<?php
/**
* Developed by Mathew Tinsley (tinsley@tinsology.net)
* http://tinsology.net
*
* Version: 1.0 (December 1st 2010)
* API Version: 2010-10-01 (http://docs.amazonwebservices.com/AWSECommerceService/2010-10-01/DG/)
*
*
* Copyright 2010 Mathew Tinsley (email: tinsley@tinsology.net)
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.

* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.

* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
class AmazonDataParser {
	
	private $result;
	private $buffer;
	
	public function __construct($result, $trim = true)
	{
		if($trim)
			unset($result->OperationRequest);

		$this->result = (array) $result;
		$this->buffer = array();
		
		$this->toArray($this->result);
	}
	
	/*
	public function parse($operation, &$paths, $base = '')
	{
		if($operation == 'ItemLookup')
			return itemLookupParse($paths, $base);
	}
	*/
	
	/**
	* Extracts a particular element from result
	*
	* $paths is an array of arrays where each element is
	* of the form array('key' => '', 'path' => '', 'callback' => '')
	* key is the index of the array the data will be stored in
	* path is a comma separated list or array of indicies where
	* the last index is the target element
	* callback (optional) is the function that will be applied
	* to the target. ex $target = callback($target)
	*/
	public function parse(&$paths, $base = '')
	{
		$data = array();
		for($i = 0; $i < count($paths); $i++)
		{
			$path 		= $paths[$i]['path'];
			$key 		= $paths[$i]['key'];
			$callback 	= isset($paths[$i]['callback']) ? $paths[$i]['callback'] : null;
			
			if(!is_array($path))
				$path = explode(',', $path);
				
			if($base != '' && count($base) > 0)
			{
				if(!is_array($base))
					$base = explode(',', $base);
					
				$path = array_merge($base, $path);
			}
				
			$this->fetch($path, $this->result, $callback);
			
			if(count($this->buffer) > 1)
				$data[$key] = $this->buffer;
			elseif(count($this->buffer) == 1)
				$data[$key] = $this->buffer[0];
			else
				$data[$key] = null;
				
			$this->buffer = array();
		}
		
		return $data;
	}
	
	private function fetch(&$path, &$data, $callback)
	{
		$curr = array_shift($path);
		
			//if there are multiple instances
		if(isset($data[1]))
		{
			$path[] = $curr;
			for($i = 0; $i < count($data); $i++)
			{
				$tpath = $path;
				$this->fetch($tpath, $data[$i], $callback);
			}
		}	//if this is the last node in the path
		elseif(count($path) == 0)
		{
			if($callback != '' && function_exists($callback))
				$this->buffer[] = $callback($data[$curr]);
			else
				$this->buffer[] = $data[$curr];
		}	//move on to the next node
		else
		{
			if(isset($data[$curr]))
				$this->fetch($path, $data[$curr], $callback);
			else
				return; //dead end
		}
	}
	
	private function toArray(&$data = null)
	{
		if($data === null)
			$data = &$this->result;

		foreach($data as $key => $value)
		{
			if(!is_array($data[$key]) && is_object($data[$key]))
				$data[$key] = (array) $value;

			if(is_array($data[$key]))
				$this->toArray($data[$key]);
		}
	}
}
?>