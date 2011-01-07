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

namespace ManiaLive\Gui\Handler;

class Event extends \ManiaLive\Event\Event
{
	protected $action;
	protected $login;
	
	function __construct($source, $login, $action)
	{
		parent::__construct($source);
		
		$this->action = $action;
		$this->login = $login;
	}
	
	function fireDo($listener)
	{
		$listener->onActionClick($this->login, $this->action);
	}
}

?>