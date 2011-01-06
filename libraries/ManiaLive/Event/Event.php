<?php 
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\Event;

abstract class Event
{
	protected $source;
	
	final static function getClass()
	{
		return get_called_class();
	}
	
	function __construct($source)
	{
		$this->source = $source;
	}
	
	function getSource()
	{
		return $this->source;
	}
	
	abstract function fireDo($listener);
}

?>