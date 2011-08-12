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

/**
 * @author Florian Schnell
 */
class Event extends \ManiaLive\Event\Event
{
	const ON_THREAD_START = 1;
	const ON_THREAD_RESTART = 2;
	const ON_THREAD_DIES = 3;
	const ON_THREAD_TIMES_OUT = 4;
	
	protected $onWhat;
	
	function __construct($source, $onWhat)
	{
		parent::__construct($source);
		
		$this->onWhat = $onWhat;
	}
	
	function fireDo($listener)
	{
		$method = null;
		$params = array();
		
		switch ($this->onWhat)
		{
			case self::ON_THREAD_START:
				$params[] = $this->source;
				$params[] = false;
				$method = 'onThreadStart';
				break;
			case self::ON_THREAD_RESTART:
				$params[] = $this->source;
				$params[] = true;
				$method = 'onThreadRestart';
				break;
			case self::ON_THREAD_DIES:
				$params[] = $this->source;
				$method = 'onThreadDies';
				break;
			case self::ON_THREAD_TIMES_OUT:
				$params[] = $this->source;
				$method = 'onThreadTimesOut';
				break;
		}
		
		if ($method != null)
		{
			call_user_func_array(array($listener, $method), $params);
		}
	}
}