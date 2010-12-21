<?php

namespace ManiaLive\DedicatedApi\Callback;

class Event extends  \ManiaLive\Event\Event
{
	protected $method;
	protected $parameters;
	
	function __construct($source, $method, $parameters)
	{
		parent::__construct($source);
		$this->method = $method;
		$this->parameters = $parameters;
	}
	
	function fireDo($listener)
	{
		call_user_func_array(array($listener, 'on'.$this->method), $this->parameters);
	}
}

?>