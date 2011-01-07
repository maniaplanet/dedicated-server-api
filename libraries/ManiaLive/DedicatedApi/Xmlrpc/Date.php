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

namespace ManiaLive\DedicatedApi\Xmlrpc;

class Date 
{
	public $year;
	public $month;
	public $day;
	public $hour;
	public $minute;
	public $second;

	function __construct($time) 
	{
		// $time can be a PHP timestamp or an ISO one
		if (is_numeric($time)) 
		{
			$this->parseTimestamp($time);
		} 
		else 
		{
			$this->parseIso($time);
		}
	}

	function parseTimestamp($timestamp) 
	{
		$this->year = date('Y', $timestamp);
		$this->month = date('Y', $timestamp);
		$this->day = date('Y', $timestamp);
		$this->hour = date('H', $timestamp);
		$this->minute = date('i', $timestamp);
		$this->second = date('s', $timestamp);
	}

	function parseIso($iso) 
	{
		$this->year = substr($iso, 0, 4);
		$this->month = substr($iso, 4, 2);
		$this->day = substr($iso, 6, 2);
		$this->hour = substr($iso, 9, 2);
		$this->minute = substr($iso, 12, 2);
		$this->second = substr($iso, 15, 2);
	}

	function getIso() 
	{
		return $this->year.$this->month.$this->day.'T'.$this->hour.':'.$this->minute.':'.$this->second;
	}

	function getXml() 
	{
		return '<dateTime.iso8601>'.$this->getIso().'</dateTime.iso8601>';
	}

	function getTimestamp() 
	{
		return mktime($this->hour, $this->minute, $this->second, $this->month, $this->day, $this->year);
	}
}

?>