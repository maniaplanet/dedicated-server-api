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

class Time
{
	static function fromTM($timestamp, $signed = false)
	{
		$time = (int)$timestamp;
		
		$negative = ($time < 0);
		if ($negative)
		{
			$time = abs($time);
		}
		
		$cent = str_pad(($time % 1000), 3, '0', STR_PAD_LEFT);
		$time = floor($time / 1000);
		$sec = str_pad($time % 60, 2, '0', STR_PAD_LEFT);
		$min = str_pad(floor($time / 60), 1, '0');
		$time = $min.':'.$sec.'.'.$cent;
		
		if ($signed)
		{
			return ($negative ? '-'.$time : '+'.$time);
		}
		else
		{
			return $time;
		}
	}
}

?>