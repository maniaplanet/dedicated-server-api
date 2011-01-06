<?php
/**
 * @copyright NADEO (c) 2010
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