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
	protected $onWhat;
	
	const ON_WINDOW_CLOSE = 0;
	const ON_WINDOW_RECOVER = 1;
	
	function __construct($source, $onWhat, $login)
	{
		parent::__construct($source);
		
		$this->login = $login;
		$this->onWhat = $onWhat;
	}
	
	function fireDo($listener)
	{
		switch ($this->onWhat)
		{
			case self::ON_WINDOW_CLOSE:
				$listener->onWindowClose($this->login, $this->source);
				break;
			case self::ON_WINDOW_RECOVER:
				$listener->onWindowRecover($this->login, $this->source);
				break;
		}
		
	}
}

?>