<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 2453 $:
 * @author      $Author: florian $:
 * @date        $Date: 2011-02-17 19:02:35 +0100 (jeu., 17 févr. 2011) $:
 */

namespace ManiaLive\Cache;

use ManiaLive\Cache\Event;
use ManiaLive\Event\Dispatcher;
use ManiaLive\Utilities\Singleton;

/**
 * Stores values from all parts of ManiaLive.
 * @author Florian Schnell
 */
class Cache extends Singleton
	implements \ManiaLive\Features\Tick\Listener
{
	protected static $instanceReturned = false;
	protected static $storage = array();
	
	/**
	 * Will return only one instance.
	 * @return \ManiaLive\Cache\Cache
	 */
	static function getInstance()
	{
		if (self::$instanceReturned)
		{
			return null;
		}
		
		self::$instanceReturned = true;
		
		return parent::getInstance();
	}
	
	/**
	 * Can not be called from outside.
	 * Instanciate this class with getInstance.
	 */
	protected function __construct()
	{
		Dispatcher::register(\ManiaLive\Features\Tick\Event::getClass(), $this);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Features/Tick/ManiaLive\Features\Tick.Listener::onTick()
	 */
	function onTick()
	{
		foreach (self::$storage as $key => $entry)
		{
			if (!$entry->isAlive())
			{
				$entryOld = self::$storage[$key];
				
				unset(self::$storage[$key]);
				
				Dispatcher::dispatch(new Event(Event::ON_EVICT, array($entryOld)));
			}
		}
	}
	
	/**
	 * Method that can be called by intern
	 * ManiaLive components to cache data.
	 * @param string $key
	 * @param mixed $value
	 * @param integer $timeToLive
	 */
	function store($key, $value, $timeToLive = null)
	{
		$entry = new Entry($key, $value, $timeToLive);
		
		Dispatcher::dispatch(new Event(Event::ON_STORE, array($entry)));
		
		self::$storage[$key] = $entry;
		
		return $entry;
	}
	
	/**
	 * Intern ManiaLive components can get
	 * data from the cache with this method.
	 * @param string $key
	 */
	function fetch($key)
	{
		if (isset(self::$storage[$key]))
		{
			return self::$storage[$key]->value;
		}
		else
		{
			throw new NotFoundException("The entry with the specified key '$key' could not be found!");
		}
	}
	
	/**
	 * Stores a value in the cache.
	 * It will be stored in the module
	 * its namespace.
	 * @param object $module
	 * @param string $key
	 * @param mixed $value
	 * @param integer $timeToLive
	 */
	static function storeInModuleCache($module, $key, $value, $timeToLive = null)
	{
		$prefix = spl_object_hash($module);
		return parent::getInstance()->store($prefix . '_' . $key, $value, $timeToLive);
	}
	
	/**
	 * Gets a value from the cache.
	 * Only data from a specific module's namespace
	 * can be retrieved.
	 * @param object $module
	 * @param string $key
	 * @throws NotFoundException
	 */
	static function fetchFromModuleCache($module, $key)
	{
		$prefix = spl_object_hash($module);
		return parent::getInstance()->fetch($prefix . '_' . $key);
	}
	
	/**
	 * Gets the path to a key that is located
	 * within a module's namespace.
	 * @param object $module
	 * @param string $key
	 */
	static function getModuleKeyPath($module, $key)
	{
		return spl_object_hash($module) . '_' . $key;
	}
}

class Exception extends \Exception {}
class LockedException extends Exception {}
class NotFoundException extends Exception {}

?>