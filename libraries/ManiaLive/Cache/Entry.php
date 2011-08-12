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

namespace ManiaLive\Cache;

/**
 * You can store this in a cache.
 * @author Florian Schnell
 */
class Entry
{
	public $key;
	public $value;
	protected $module;
	protected $timeToLive;
	protected $timeToDie;
	
	function __construct($key, $value, $timeToLive = null)
	{
		$this->key = $key;
		$this->value = $value;
		
		$this->timeToDie = null;
		if ($timeToLive !== null)
		{
			$this->timeToLive = $timeToLive;
			$this->timeToDie = $timeToLive + time();
		}
	}
	
	function isAlive()
	{
		return ($this->timeToDie > time());
	}
	
	function getTimeToLive()
	{
		return $this->timeToLive;
	}
	
	function getTimeToDie()
	{
		return $this->timeToDie;
	}
	
	function getTimeLeft()
	{
		return $this->timeToDie - time();
	}
	
	function setTimeToLive($timeToLive)
	{
		$this->timeToDie = null;
		if ($timeToLive !== null)
		{
			$this->timeToLive = $timeToLive;
			$this->timeToDie = $timeToLive + time();
		}
	}
}

?>