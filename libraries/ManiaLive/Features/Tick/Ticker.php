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

namespace ManiaLive\Features\Tick;

use ManiaLive\Event\Dispatcher;

class Ticker extends \ManiaLive\Application\Adapter
{
	protected $microtime;
	
	function __construct()
	{
		$this->microtime = microtime(true);
		Dispatcher::register(\ManiaLive\Application\Event::getClass(), $this);
	}
	
	function onPreLoop()
	{
		$microtime = microtime(true);
		if($microtime - $this->microtime > 1)
		{
			$this->microtime = $microtime;
			Dispatcher::dispatch(new Event($this));
		}
	}
}

?>