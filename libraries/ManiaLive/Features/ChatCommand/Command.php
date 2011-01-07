<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 1709 $:
 * @author      $Author: svn $:
 * @date        $Date: 2011-01-07 14:06:13 +0100 (ven., 07 janv. 2011) $:
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