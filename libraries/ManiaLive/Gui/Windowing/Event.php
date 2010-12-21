<?php

namespace ManiaLive\Gui\Windowing;

/**
 * This Event class provides callbacks for window-specific events.
 * eg. when a window is being closed.
 * 
 * @author Florian Schnell
 * @copyright 2010 NADEO
 */
class Event extends \ManiaLive\Event\Event
{
	protected $login;
	
	function __construct($source, $login)
	{
		parent::__construct($source);
		
		$this->login = $login;
	}
	
	function fireDo($listener)
	{
		$listener->onWindowClose($this->login, $this->source);
	}
}

?>