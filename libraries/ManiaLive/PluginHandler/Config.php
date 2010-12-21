<?php

namespace ManiaLive\PluginHandler;

use ManiaLive\Application\FatalException;

class Config extends \ManiaLive\Config\Configurable
{
	protected $plugins = array();
	
	public $load;
	
	function __set($name, $value)
	{
		$this->plugins[$name] = $value;
	}
	
	function __get($name)
	{
		if (!isset($this->plugins[$name])) $this->plugins[$name] = array();
		return $this->plugins[$name];
	}
	
	function validate()
	{
		$this->setDefault('load', array());
	}
}

?>