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

namespace ManiaLive\Gui\Windowing;

/**
 * This Event class provides callbacks for window-specific events.
 * eg. when a window is being closed.
 * 
 * @author Florian Schnell
 */
class Event extends \ManiaLive\Event\Event
{
	protected $login;
	
	function __construct($source, $login)
	{
		parent::__construct($source);
		
		$this->login = $login;
	}
	
	function fireDo($listener)
	{
		$listener->onWindowClose($this->login, $this->source);
	}
}

?>