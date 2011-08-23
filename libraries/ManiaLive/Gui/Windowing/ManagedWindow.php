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
use ManiaLib\Gui\Elements\Icons128x32_1;
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
	private $panel;
	private $buttonMin;
	private $buttonMax;
	private $maximizable;
	private $maximized;
	private $oldX;
	private $oldY;
	
	/**
	 * This will create a new instance of the window
	 * that extends this class.
	 * @param string $login
	 * @param bool $singleton
	 * @return \ManiaLive\Gui\Windowing\ManagedWindow
	 * @throws \Exception
	 */
	static function Create($login, $singleton = true)
	{
		if ($login == self::RECIPIENT_ALL)
		{
			throw new \Exception('You can not send a window instance of ManagedWindow to more than one player!');
		}
		
		return parent::Create($login, $singleton);
	}
	
	/**
	 * Use the static Create method to instanciate
	 * a new object of that class.
	 * @param string $login
	 */
	protected function __construct($login)
	{
		$this->oldX = null;
		$this->oldY = null;
		
		$this->panel = new Panel();
		$this->addComponent($this->panel);
		
		// create minimize button ...
		$this->buttonMin = new Label();
		$this->buttonMin->setStyle(Label::TextCardRaceRank);
		$this->buttonMin->setText('$000_');
		
		$this->buttonMax = new Icons128x32_1(3);
		$this->buttonMax->setSubStyle(Icons128x32_1::Windowed);
		
		$this->setMaximizable(false);
		
		parent::__construct($login);
		
		$this->buttonMin->setAction($this->callback(array(WindowHandler::getInstance(), 'sendCurrentWindowToTaskbar')));
		$this->addComponent($this->buttonMin);
		
		$this->buttonMax->setAction($this->callback('maximizeWindow'));
		$this->addComponent($this->buttonMax);
	}
	
	/**
	 * Buzz all windows of the given type.
	 * Will inform players that this window has got some
	 * new information for them.
	 */
	static function Buzz()
	{
		$class_name = get_called_class();
		$windows = self::GetAll();
		foreach ($windows as $window)
		{
			$thumbnail = WindowHandler::getThumbnail($window);
			if ($thumbnail)
			{
				$thumbnail->enableHighlight();
			}
		}
	}
	
	/**
	 * Redraws all window instances that are
	 * currently shown on player screens and
	 * send buzz signal if a window is minimized.
	 */
	static function Redraw()
	{
		$windows = self::GetAll();
		foreach ($windows as $window)
		{
			if ($window->isVisible())
			{
				$window->show();
			}
			else
			{
				$thumb = WindowHandler::getThumbnail($window);
				if ($thumb)
				{
					$thumb->enableHighlight();
				}
			}
		}
	}
	
	/**
	 * Sets the window's title.
	 * @param string $title
	 */
	function setTitle($title)
	{
		$this->panel->setTitle($title);
	}
	
	/**
	 * @return string The window its title.
	 */
	function getTitle()
	{
		return $this->panel->getTitle();
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
		$this->buttonMax->setVisibility($this->maximizable);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Gui/Windowing/ManiaLive\Gui\Windowing.Window::resize()
	 */
	final function resize($oldX, $oldY)
	{
		$this->buttonMax->setPosition(3.5, 2.5);
		
		if ($this->maximizable)
		{
			$this->buttonMin->setPosition(7, 2.5);
		}
		else
		{
			$this->buttonMin->setPosition(3.5, 2.5);
		}
		
		$this->panel->setSize($this->sizeX, $this->sizeY);
		
		$this->onResize();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Gui/Windowing/ManiaLive\Gui\Windowing.Window::move()
	 */
	final function move($oldX, $oldY, $oldZ)
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
		
		$state = $this->visible;
		$this->visible = true;
		
		if (WindowHandler::addManaged($this, $login))
		{
			if ($this->maximized)
			{
				if (!$this->oldX && !$this->oldY)
				{
					$this->oldX = $this->sizeX;
					$this->oldY = $this->sizeY;
				}
				$this->setPosition(-64, -48);
				$this->setSize(128, 96);
			}
			else
			{
				if ($this->oldX || $this->oldY)
				{
					$this->setSize($this->oldX, $this->oldY);
					$this->centerOnScreen();
					$this->oldX = null;
					$this->oldY = null;
				}
			}	
			
			// call recover function when Window state changes
			// from hidden to visible
			if (!$state)
			{
				$this->onShow();
				Dispatcher::dispatch(new Event($this, Event::ON_WINDOW_RECOVER, $login));
			}
		}
		else
		{
			$this->visible = $state;
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
		
		$state = $this->visible;
		$this->visible = false;
		
		// add to drawstack button ...
		if (WindowHandler::addManaged($this, $login))
		{
			// invoke cleanup function ..
			$this->onHide();
			
			// this is not maximized anymore
			if ($this->maximized)
			{
				if ($this->oldX || $this->oldY)
				{
					$this->setSize($this->oldX, $this->oldY);
					$this->centerOnScreen();
					$this->oldX = null;
					$this->oldY = null;
				}
				$this->maximized = false;
			}	
			
			// window is closed ...
			Dispatcher::dispatch(new Event($this, Event::ON_WINDOW_CLOSE, $login));
		}
		else
		{
			$this->visible = $state;
		}
	}
	
	function destroy()
	{
		parent::destroy();
		
		$this->buttonMin = null;
		$this->buttonMax = null;
		$this->panel = null;
	}
}

?>