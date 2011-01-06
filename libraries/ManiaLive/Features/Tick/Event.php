<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\Features\Tick;

class Event extends \ManiaLive\Event\Event
{
	protected $microtime;
	
	function __construct($source)
	{
		parent::__construct($source);
		$this->microtime = microtime(true);		
	}
	
	function fireDo($listener)
	{
		$listener->onTick();
	}
}

?>