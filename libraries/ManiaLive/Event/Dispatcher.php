<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\Event;

use ManiaLive\Application\ErrorHandling;

use ManiaLive\Utilities\Console;

abstract class Dispatcher
{
	static protected $listeners = array();
	
	public static function register($eventClass, Listener $listener)
	{
		self::$listeners[$eventClass][] = $listener;
	}
	
	public static function unregister($eventClass, Listener $listener)
	{
		if(array_key_exists($eventClass, self::$listeners) && is_array(self::$listeners[$eventClass]))
		{
			foreach(self::$listeners[$eventClass] as $key=>$value)
			{
				if($value === $listener)
				{
					unset(self::$listeners[$eventClass][$key]);
				}
			}			
		}
	}
	
	public static function dispatch(Event $event)
	{
		$class = get_class($event);
		if(array_key_exists($class, self::$listeners) && is_array(self::$listeners[$class]))
		{
			try
			{
				foreach(self::$listeners[$class] as $listener)
				{
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
			catch (\Exception $e)
			{
				ErrorHandling::processEventException($e);
			}
		}
	}
}

?>