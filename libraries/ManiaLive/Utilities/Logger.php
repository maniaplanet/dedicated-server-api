<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLive\Utilities;

use ManiaLive\Config\Loader;

class Logger
{
	private static $logs = array();
	private $enabled;
	private $path;
	
	/**
	 * @param string $name
	 * @param string $subfolder
	 * @return \ManiaLive\Utilities\Logger
	 */
	static function getLog($name, $subfolder = '')
	{
		$id = $subfolder.'_'.$name;
		if (isset(self::$logs[$id]))
		{
			return self::$logs[$id];
		}
		
		$log = new Logger($name, $subfolder);
		self::$logs[$id] = $log;
		return $log;
	}
	
	function __construct($name, $subfolder = '')
	{
		// if path does not exist ...
		if(!is_dir(Loader::$config->logsPath))
		{
			mkdir(Loader::$config->logsPath, "0777", true);
		}
			
		// build path ...
		if ($subfolder != '') 
		{
			$subfolder = $subfolder . '_';
		}
		
		// append filename to path ...
		$this->path = Loader::$config->logsPath . '/';
		$this->path .= Loader::$config->logsPrefix;
		$this->path .= $subfolder;
		$this->path .= 'log_' . $name . '.txt';
		
		$this->enabled = true;
	}
	
	function enableLog()
	{
		$this->enabled = true;
	}
	
	function disableLog()
	{
		$this->enabled = false;
	}
	
	function write($text, $addDate = false)
	{		
		if ($this->enabled)
		{
			if($addDate)
			{
				$text = date("Y.m.d_H:i ") . $text;
			}
			error_log($text, 3, $this->path);
		}
	}
}
?>