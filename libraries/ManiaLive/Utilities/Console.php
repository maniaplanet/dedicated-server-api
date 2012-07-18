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

use ManiaLive\Config\Config;
use DedicatedApi\Structures\Player;

abstract class Console
{
	public static function println($string)
	{
		Logger::getLog('Runtime')->write($string);
		if(Config::getInstance()->verbose)
			echo $string.PHP_EOL;
	}

	public static function print_rln($string)
	{
		$line = print_r($string, true);
		Logger::getLog('Runtime')->write($line);
		if(Config::getInstance()->verbose)
			echo $line.PHP_EOL;
	}

	public static function getDatestamp()
	{
		return date("H:i:s");
	}

	public static function printlnFormatted($string)
	{
		$line = '['.self::getDatestamp().'] '.$string;
		self::println($line);
	}

	public static function printDebug($string)
	{
		if(Config::getInstance()->debug)
		{
			$line = '['.self::getDatestamp().'|Debug] '.$string;
			self::println($line);
		}
	}

	public static function printPlayerBest(Player $player)
	{
		$str = array();
		$str[] = '[Time by '.$player->login.' : '.$player->bestTime.']';
		foreach($player->bestCheckpoints as $i => $time)
		{
			$str[] = '  [Checkpoint #'.$i.': '.$time.']';
		}
		Console::println(implode(PHP_EOL, $str));
	}

	public static function printPlayerScore(Player $player)
	{
		$str = array();
		$str[] = '[Score by '.$player->login.' : '.$player->score.']';
		foreach($player->bestCheckpoints as $i => $score)
		{
			$str[] = '  [Checkpoint #'.$i.': '.$score.']';
		}
		Console::println(implode(PHP_EOL, $str));
	}
}

?>