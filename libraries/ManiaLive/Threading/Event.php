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

namespace ManiaLive\Threading;

class Event extends \ManiaLive\Event\Event
{
	const ON_THREAD_START     = 1;
	const ON_THREAD_RESTART   = 2;
	const ON_THREAD_DIES      = 4;
	const ON_THREAD_TIMES_OUT = 8;
	
	protected $thread;
	
	function __construct($onWhat, $thread)
	{
		parent::__construct($onWhat);
		$this->thread = $thread;
	}
	
	function fireDo($listener)
	{
		switch($this->onWhat)
		{
			case self::ON_THREAD_START: $listener->onThreadStart($this->thread); break;
			case self::ON_THREAD_RESTART: $listener->onThreadRestart($this->thread); break;
			case self::ON_THREAD_DIES: $listener->onThreadDies($this->thread); break;
			case self::ON_THREAD_TIMES_OUT: $listener->onThreadTimesOut($this->thread); break;
		}
	}
}