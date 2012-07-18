<?php
/**
 * Profiler Plugin - Show statistics about ManiaLive
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\Standard\Profiler;

use \ManiaLive\Event\Dispatcher;
use \ManiaLive\Application\Listener as AppListener;
use \ManiaLive\Application\Event as AppEvent;
use \ManiaLive\Features\Tick\Listener as TickListener;
use \ManiaLive\Features\Tick\Event as TickEvent;
use \DedicatedApi\Xmlrpc\Client;

/**
 * Description of Monitor
 */
class Monitor extends \ManiaLib\Utils\Singleton implements AppListener, TickListener
{
	private $running = false;
	private $loopStart;
	private $loopCount;
	private $tickCount;
	
	private $lastNetwork;
	
	function start()
	{
		$this->running = true;
		$this->loopStart = microtime(true);
		$this->loopCount = 0;
		$this->tickCount = 0;
		$this->lastNetwork = array(Client::$received, Client::$sent);
		Dispatcher::register(AppEvent::getClass(), $this, AppEvent::ON_PRE_LOOP);
		Dispatcher::register(TickEvent::getClass(), $this);
	}
	
	function stop()
	{
		$this->running = false;
		Dispatcher::unregister(AppEvent::getClass(), $this);
		Dispatcher::unregister(TickEvent::getClass(), $this);
	}
	
	function onTick()
	{
		++$this->tickCount;
		$time = microtime(true);
		
		Dispatcher::dispatch(new Event(Event::ON_NEW_CPU_VALUE, round($this->loopCount / ($time - $this->loopStart), 2)));
		if($this->tickCount % 3 == 0)
			Dispatcher::dispatch(new Event(Event::ON_NEW_MEMORY_VALUE, memory_get_usage()));
		Dispatcher::dispatch(new Event(Event::ON_NEW_NETWORK_VALUE, array(Client::$received - $this->lastNetwork[0], Client::$sent - $this->lastNetwork[1])));
		
		$this->loopCount = 0;
		$this->loopStart = $time;
		$this->lastNetwork = array(Client::$received, Client::$sent);
	}
	
	function onPreLoop()
	{
		++$this->loopCount;
	}
	
	function onInit() {}
	function onRun() {}
	function onPostLoop() {}
	function onTerminate() {}
}

?>