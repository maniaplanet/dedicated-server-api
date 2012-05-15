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
		if(isset(self::$logs[$id]))
			return self::$logs[$id];

		$log = new Logger($name, $subfolder);
		self::$logs[$id] = $log;
		return $log;
	}

	private function __construct($name, $subfolder = '')
	{
		// if path does not exist ...
		$config = \ManiaLive\Config\Config::getInstance();
		if(!is_dir($config->logsPath))
			mkdir($config->logsPath, '0777', true);

		if($subfolder != '')
			$subfolder = $subfolder.'_';

		$this->path = $config->logsPath.'/'.$config->logsPrefix.$subfolder.'log_'.$name.'.txt';
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

	function write($text)
	{
		if($this->enabled)
			error_log('['.date('Y.m.d H:i:s').'] '.$text.PHP_EOL, 3, $this->path);
	}
}

?>