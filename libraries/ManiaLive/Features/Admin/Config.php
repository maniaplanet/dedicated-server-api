<?php

namespace ManiaLive\Features\Admin;

class Config extends \ManiaLive\Config\Configurable
{
	public $logins;
	
	function validate()
	{
		$this->setDefault('logins', array());
	}
}

?>