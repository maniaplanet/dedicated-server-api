<?php
/**
 * @copyright NADEO (c) 2010
 */

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