<?php

namespace ManiaLive\PluginHandler;

class Event extends \ManiaLive\Event\Event
{
	function __construct($source)
	{
		parent::__construct($source);
	}
	
	function fireDo($listener)
	{
		$listener->onPluginLoaded($this->source);
	}
}
?>