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

use ManiaLive\Data\Storage;
use ManiaLib\Gui\Elements\Icons64x64_1;
use ManiaLib\Gui\Cards\Navigation\Button;
use ManiaLib\Gui\Elements\Label;
use ManiaLive\Gui\Windowing\Controls\Panel;
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
	protected $panel;
	protected $btn_min;
	protected $btn_max;
	protected $maximizable;
	protected $maximized;
	
	protected $old_x;
	protected $old_y;
	
	protected function __construct($login)
	{
		$this->old_x = null;
		$this->old_y = null;
		
		$this->panel = new Panel();
		$this->addComponent($this->panel);
		
		// create minimize button ...
		$this->btn_min = new Label();
		$this->btn_min->setStyle(Label::TextCardRaceRank);
		$this->btn_min->setText('$000_');
		
		$this->btn_max = new Icons64x64_1(3);
		$this->btn_max->setSubStyle(Icons64x64_1::Windowed);
		
		$this->setMaximizable(false);
		
		parent::__construct($login);
		
		$this->btn_min->setAction($this->callback(array(WindowHandler::getInstance(), 'sendCurrentWindowToTaskbar')));
		$this->addComponent($this->btn_min);
		
		$this->btn_max->setAction($this->callback('maximizeWindow'));
		$this->addComponent($this->btn_max);
	}
	
	/**
	 * Whether this window is currently maximized.
	 * @return bool
	 */
	function isMaximized()
	{
		return $this->maximized;
	}
	
	/**
	 * Set this window maximized and redraw
	 * it onto the screen.
	 */
	function maximizeWindow()
	{
		$this->maximized = !$this->maximized;
		$this->show();
	}
	
	/**
	 * Show or hide the maximize button.
	 * @param bool $max
	 */
	function setMaximizable($max = true)
	{
		$this->maximizable = $max;
		$this->btn_max->setVisibility($this->maximizable);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Gui/Windowing/ManiaLive\Gui\Windowing.Window::resize()
	 */
	final function resize()
	{
		$this->btn_max->setPosition(5, 1.7);
		
		if ($this->maximizable)
		{
			$this->btn_min->setPosition(8.5, 0.8);
		}
		else
		{
			$this->btn_min->setPosition(5, 0.8);
		}
		
		$this->panel->setSize($this->sizeX, $this->sizeY);
		
		$this->onResize();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Gui/Windowing/ManiaLive\Gui\Windowing.Window::move()
	 */
	final function move()
	{
		$this->onMove();
	}
	
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
		
		if (WindowHandler::addManaged($this, $login))
		{
			if ($this->maximized)
			{
				if (!$this->old_x && !$this->old_y)
				{
					$this->old_x = $this->sizeX;
					$this->old_y = $this->sizeY;
				}
				$this->setPosition(-64, -48);
				$this->setSize(128, 96);
			}
			else
			{
				if ($this->old_x || $this->old_y)
				{
					$this->setSize($this->old_x, $this->old_y);
					$this->centerOnScreen();
					$this->old_x = null;
					$this->old_y = null;
				}
			}	
			
			// call recover function when Window state changes
			// from hidden to visible
			if ($state)
			{
				$this->onShow();
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
		if (WindowHandler::addManaged($this, $login))
		{
			// invoke cleanup function ..
			$this->onHide();
			
			// this is not maximized anymore
			if ($this->maximized)
			{
				if ($this->old_x || $this->old_y)
				{
					$this->setSize($this->old_x, $this->old_y);
					$this->centerOnScreen();
					$this->old_x = null;
					$this->old_y = null;
				}
				$this->maximized = false;
			}	
			
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