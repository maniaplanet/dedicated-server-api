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

use ManiaLive\Event\Dispatcher;

/**
 * This window will be managed by the windowing system.
 * if there is more than one window displayed at a time,
 * then it will put the oldest ones into a "taskbar".
 *
 * @author Florian Schnell
 */
abstract class ManagedWindow extends \ManiaLive\Gui\Windowing\Window
{
	/**
	 * Display this window on the screen.
	 * If there is a managed window already, then put it into the taskbar.
	 * @param string $login Update or show window for specific player only.
	 */
	public function show($login = null)
	{
		$this->uptodate = false;
		
		if ($this->login != null)
		{
			$login = $this->login;
		}
		
		$state = $this->isHidden;
		$this->isHidden = false;
		
		if ($this->windowHandler->addManaged($this, $login))
		{
			// call recover function when Window state changes
			// from hidden to visible
			if ($state)
			{
				$this->onRecover();
				Dispatcher::dispatch(new Event($this, Event::ON_WINDOW_RECOVER, $login));
			}
		}
		else
		{
			$this->isHidden = $state;
		}
	}
	
	/**
	 * Hide this window from the screen.
	 * Empty the managed window space.
	 * @param string $login
	 */
	public function hide($login = null)
	{
		$this->uptodate = false;
		
		if ($this->login != null)
		{
			$login = $this->login;
		}
		
		$state = $this->isHidden;
		$this->isHidden = true;
		
		// add to drawstack button ...
		if ($this->windowHandler->addManaged($this, $login))
		{
			// invoke cleanup function ..
			$this->onHide();
			
			// window is closed ...
			Dispatcher::dispatch(new Event($this, Event::ON_WINDOW_CLOSE, $login));
		}
		else
		{
			$this->isHidden = $state;
		}
	}
}

?>