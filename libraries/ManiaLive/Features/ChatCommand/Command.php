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