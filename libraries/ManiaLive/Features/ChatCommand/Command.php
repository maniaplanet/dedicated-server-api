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
	
	/**
	 * The name of the command
	 * @var string
	 */
	public $name;
	/**
	 * The number of argument to use the command
	 * @var int
	 */
	public $parametersCount;
	/**
	 * The list of logins allowed to use this command
	 * @var array
	 */
	public $authorizedLogin;
	/**
	 * Set to true to have the login as First parameter.
	 * This parameter is not included int the number of parametersCount
	 * @var bool
	 */
	public $addLoginAsFirstParameter;
	/**
	 * The method call when the command is used
	 * @var callback
	 */
	public $callback;
	/**
	 * Log the usage of this command
	 * @var bool
	 */
	public $log = true;
	/**
	 * Set this parameter to true if you want this command visible with the /help command
	 * @var bool
	 */
	public $isPublic = false;
	/**
	 * Put here a text which will be display with the /man command to help the user
	 * or give hime more details about the command
	 * @var string
	 */
	public $help;
}

?>