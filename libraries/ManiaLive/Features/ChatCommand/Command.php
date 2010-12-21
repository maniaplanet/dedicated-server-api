<?php

namespace ManiaLive\Features\ChatCommand;

class Command
{
	function __construct($name, $parametersCount, $authorizedLogin = array())
	{
		$this->name = $name;
		$this->parametersCount = $parametersCount;
		$this->authorizedLogin = $authorizedLogin;
	}
	
	public $name;
	public $parametersCount;
	public $authorizedLogin;
	public $addLoginAsFirstParameter;
	public $callback;
	public $log = true;
	public $isPublic = false;
	public $help;
}

?>