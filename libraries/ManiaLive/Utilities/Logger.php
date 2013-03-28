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
	private static $staticPath;
	private $prefix;
	private static $staticPrefix;
	protected static $loaded = false;

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
		if($this->enabled)
		{
			array_unshift($tags, date('Y.m.d H:i:s'));
			file_put_contents($this->path, '['.implode('][', $tags).'] '.$text.PHP_EOL, FILE_APPEND);
		}
	}

	static function info($message, $addDate = true, $tags=array())
	{
		self::log($message, $addDate, 'info.log', $tags);
	}

	static function error($message, $addDate = true, $tags=array())
	{
		self::log($message, $addDate, 'error.log', $tags);
	}

	static function runtime($message, $addDate = true, $tags=array())
	{
		if(\ManiaLive\Config\Config::getInstance()->runtimeLog)
		{
			self::log($message, $addDate, 'runtime.log', $tags);
		}
	}

	static function debug($message, $addDate = true, $tags=array())
	{
		if(\ManiaLive\Config\Config::getInstance()->debug)
		{
			self::log($message, $addDate, 'debug.log', $tags);
		}
	}

	static function log($message, $addDate = true, $logFilename = 'debug.log', $tags=array(), $nl="\n")
	{
		if(self::load())
		{
			if($addDate)
			{
				array_unshift($tags, date('c'));
			}
			$message =($tags ? '['.implode('][', $tags).'] ' : '').print_r($message, true).$nl;
			$filename = self::$staticPath.self::$staticPrefix.$logFilename;
			file_put_contents($filename, $message, FILE_APPEND);
		}
	}

	static protected function load()
	{
		if(!self::$loaded)
		{
			$config = \ManiaLive\Config\Config::getInstance();

			if(!is_dir($config->logsPath))
			{
				if(mkdir($config->logsPath, '0777', true))
				{
					self::$loaded = true;
					self::$staticPath = $config->logsPath.'/';
					self::$staticPrefix = $config->logsPrefix ? $config->logsPrefix.'-' : '';
				}
			}
			else
			{
				self::$loaded = true;
				self::$staticPath = $config->logsPath.'/';
				self::$staticPrefix = $config->logsPrefix ? $config->logsPrefix.'-' : '';
			}
		}
		return!empty(self::$staticPath);
	}
}

?>