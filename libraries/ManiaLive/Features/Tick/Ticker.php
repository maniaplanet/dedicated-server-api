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

namespace ManiaLive\Features\Tick;

use ManiaLive\Event\Dispatcher;
use ManiaLive\Application\Adapter as AppAdapter;
use ManiaLive\Application\Event as AppEvent;

class Ticker extends AppAdapter
{
	protected $microtime;
	
	function __construct()
	{
		$this->microtime = microtime(true);
		Dispatcher::register(AppEvent::getClass(), $this, AppEvent::ON_PRE_LOOP);
	}
	
	function onPreLoop()
	{
		$microtime = microtime(true);
		if($microtime - $this->microtime > 1)
		{
			$this->microtime = $microtime;
			Dispatcher::dispatch(new Event());
		}
	}
}

?>