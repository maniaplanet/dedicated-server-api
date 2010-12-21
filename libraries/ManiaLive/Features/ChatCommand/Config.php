<?php

namespace ManiaLive\Features\ChatCommand;

class Config extends \ManiaLive\Config\Configurable
{
	public $createDocumentation;
	
	function validate()
	{
		$this->setDefault('createDocumentation', false);
	}
}

?>