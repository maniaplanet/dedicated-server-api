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
	 * @return \ManiaLive\Utilities\Logger
	 */
	static function getLog($name)
	{
		if(isset(self::$logs[$name]))
			return self::$logs[$name];

		$log = new Logger($name);
		self::$logs[$name] = $log;
		return $log;
	}

	private function __construct($name)
	{
		// if path does not exist ...
		$config = \ManiaLive\Config\Config::getInstance();
		if(!is_dir($config->logsPath))
			mkdir($config->logsPath, '0777', true);

		$this->path = $config->logsPath.'/'.($config->logsPrefix ? $config->logsPrefix.'-' : '').$name.'.txt';
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

	function write($text, $tags=array())
	{
		array_unshift($tags, date('Y.m.d H:i:s'));
		if($this->enabled)
			file_put_contents($this->path, '['.implode('][', $tags).'] '.$text.PHP_EOL, FILE_APPEND);
	}
}

?>