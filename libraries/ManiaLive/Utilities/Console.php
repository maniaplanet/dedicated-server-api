<?php

namespace ManiaLive\Utilities;

use ManiaLive\Config\Loader;

abstract class Console
{
	public static function println($string)
	{
		Logger::getLog('Runtime')->write($string.APP_NL);
		echo $string.APP_NL;
	}
	
	public static function print_rln($string)
	{
		$line = print_r($string, true);
		Logger::getLog('Runtime')->write($line.APP_NL);
		echo $line.APP_NL;
	}
	
	public static function getDatestamp()
	{
		return date("H:i:s");
	}
	
	public static function printlnFormatted($string)
	{
		$line = '[' . self::getDatestamp() . '] ' . $string;
		self::println($line);
	}
	
	public static function printDebug($string)
	{
		if (APP_DEBUG)
		{
			$line = '[' . self::getDatestamp() . '|Debug] ' . $string;
			self::println($line);
		}
	}
}

?>