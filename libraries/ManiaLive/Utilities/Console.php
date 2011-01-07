<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 1709 $:
 * @author      $Author: svn $:
 * @date        $Date: 2011-01-07 14:06:13 +0100 (ven., 07 janv. 2011) $:
 */

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