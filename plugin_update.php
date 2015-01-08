<?php
class AmazonPluginUpdate
{
	private $callbacks;
	
	private function __construct()
	{
		$this->callbacks = array();
	}
	
	private function enqueue($callback, $order)
	{
		$this->callbacks[$order] = $callback;
	}
	
	private function exec()
	{
		ksort($this->callbacks);
		foreach($this->callbacks as $callback)
			call_user_func($callback);
	}
	
	public static function go($curr, $update_dir)
	{
		if($curr == '')
			$curr = '1.0';
			
		$amazon_plugin_update = new AmazonPluginUpdate();
		$files = scandir($update_dir);
		
		foreach($files as $file)
		{
			if($file != '.' && $file != '..')
			{
				$name = substr($file, 0, strrpos($file, '.'));
				if(version_compare($name, $curr) > 0)
					require_once('plugin-update/' . $file);
			}
		}
		
		$amazon_plugin_update->exec();
	}
}
?>