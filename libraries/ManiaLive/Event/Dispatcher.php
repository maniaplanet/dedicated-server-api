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

namespace ManiaLive\Event;

use ManiaLive\Application\ErrorHandling;

abstract class Dispatcher
{
	static protected $listeners = array();
	static protected $eventsByClass = array();
	
	public static function register($eventClass, Listener $listener, $events = Event::ALL)
	{
		$listenerId = spl_object_hash($listener);
		
		if(!isset(self::$eventsByClass[$eventClass]))
		{
			$rc = new \ReflectionClass($eventClass);
			
			self::$eventsByClass[$eventClass] = $rc->getConstants();
			self::$listeners[$eventClass] = array();
			foreach(self::$eventsByClass[$eventClass] as $event)
				self::$listeners[$eventClass][$event] = array();
		}
		
		foreach(self::$eventsByClass[$eventClass] as $event)
			if($event & $events)
				self::$listeners[$eventClass][$event][$listenerId] = $listener;
	}
	
	public static function unregister($eventClass, Listener $listener, $events = Event::ALL)
	{
		$listenerId = spl_object_hash($listener);
		
		if(isset(self::$eventsByClass[$eventClass]))
			foreach(self::$eventsByClass[$eventClass] as $event)
				if($event & $events)
					unset(self::$listeners[$eventClass][$event][$listenerId]);
	}
	
	public static function dispatch(Event $event)
	{
		$eventClass = get_class($event);
		
		if(isset(self::$listeners[$eventClass]) && isset(self::$listeners[$eventClass][$event->getMethod()]))
			foreach(self::$listeners[$eventClass][$event->getMethod()] as $listener)
				try
				{
					$event->fireDo($listener);
				}
				catch(\Exception $e)
				{
					ErrorHandling::processModuleException($e);
				}
	}
}

?>