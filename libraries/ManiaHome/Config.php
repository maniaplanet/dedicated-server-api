<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */
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