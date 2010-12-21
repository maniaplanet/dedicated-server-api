<?php

namespace ManiaHome;

class Config extends \ManiaLive\Config\Configurable
{
	public $enabled;
	public $user;
	public $password;
	public $manialink;
	
	function validate()
	{
		$this->setDefault('enabled', false);
		
		if ($this->enabled)
		{
			if (!$this->user)
				throw new \ManiaLive\Config\Exception('ManiaHome is enabled but no user name given!');
			if (!$this->password)
				throw new \ManiaLive\Config\Exception('ManiaHome is enabled but no password given!');
			if (!$this->manialink)
				throw new \ManiaLive\Config\Exception('ManiaHome is enabled but no manialink given!');
		}
	}
}

?>