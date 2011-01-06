<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\PluginHandler;

/**
 * @author Florian Schnell
 */
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