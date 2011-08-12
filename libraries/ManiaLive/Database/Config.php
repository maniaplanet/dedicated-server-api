<?php
/**
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLive\Database;

class Config extends \ManiaLive\Config\Configurable
{
	public $enable;
	public $host;
	public $port;
	public $password;
	public $username;
	public $database;
	public $type;

	function validate()
	{
		$this->setDefault('enable', true);
		$this->setDefault('host', '127.0.0.1');
		$this->setDefault('port', 3306);
		$this->setDefault('username', 'root');
		$this->setDefault('password', '');
		$this->setDefault('database', '');
		$this->setDefault('type', 'MySQL');
	}

}

?>
