<?php
/**
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLive\Gui;

use ManiaLib\Gui\Elements\Icons128x32_1;
use ManiaLib\Gui\Elements\Label;
use ManiaLive\Event\Dispatcher;

/**
 * This window will be managed by the windowing system.
 * if there is more than one window displayed at a time,
 * then it will put the oldest ones into a "taskbar".
 */
class ManagedWindow extends Panel
{
	private $maximized;
	private $oldSizeX;
	private $oldSizeY;
	
	private $buttonMin;
	private $buttonMax;
	
	/**
	 * This will create a new instance of the window
	 * that extends this class.
	 * @param string $recipient
	 * @param bool $singleton
	 * @return \ManiaLive\Gui\Windowing\ManagedWindow
	 * @throws \Exception
	 */
	static function Create($recipient = null, $singleton = true)
	{
		if($recipient == null || $recipient instanceof Group)
			throw new \Exception('You can not send a window instance of ManagedWindow to more than one player!');
		
		return parent::Create($recipient, $singleton);
	}
	
	/**
	 * Buzz all windows of the given type.
	 * Will inform players that this window has got some
	 * new information for them.
	 */
	static function Buzz()
	{
		foreach(self::GetAll() as $window)
		{
			$thumbnail = GuiHandler::getInstance()->getThumbnail($window);
			if($thumbnail)
				$thumbnail->enableHighlight();
		}
	}
	
	/**
	 * Redraws all window instances that are
	 * currently shown on player screens and
	 * send buzz signal if a window is minimized.
	 */
	static function RedrawAll()
	{
		foreach(self::GetAll() as $window)
		{
			if($window->isVisible())
				$window->show();
			else
			{
				$thumbnail = GuiHandler::getInstance()->getThumbnail($window);
				if($thumbnail)
					$thumbnail->enableHighlight();
			}
		}
	}
	
	/**
	 * Use the static Create method to instanciate
	 * a new object of that class.
	 */
	protected function onConstruct()
	{
		parent::onConstruct();
		$this->oldSizeX = null;
		$this->oldSizeY = null;
		
		// create minimize button ...
		$this->buttonMin = new Label();
		$this->buttonMin->setStyle(Label::TextCardRaceRank);
		$this->buttonMin->setPosY(-2.5);
		$this->buttonMin->setText('$000_');
		$this->buttonMin->setAction(ActionHandler::getInstance()->createAction(array(GuiHandler::getInstance(), 'sendToTaskbar')));
		$this->addComponent($this->buttonMin);
		
		$this->buttonMax = new Icons128x32_1(8);
		$this->buttonMax->setSubStyle(Icons128x32_1::Windowed);
		$this->buttonMax->setPosition(9, -2.5);
		$this->buttonMax->setAction($this->createAction(array($this, 'maximize')));
		$this->addComponent($this->buttonMax);
		
		$this->setMaximizable(false);
	}
	
	/**
	 * Whether this window is currently maximized.
	 * @return bool
	 */
	final function isMaximized()
	{
		return $this->maximized;
	}
	
	/**
	 * Set this window maximized and redraw
	 * it onto the screen.
	 */
	final function maximize()
	{
		$this->maximized = !$this->maximized;
		if($this->maximized)
			$this->posZ = self::Z_MAXIMIZED;
		$this->redraw();
	}
	
	/**
	 * Show or hide the maximize button.
	 * @param bool $maximizable
	 */
	function setMaximizable($maximizable = true)
	{
		$this->buttonMax->setVisibility($maximizable);
		$this->buttonMin->setPosX($maximizable ? 18 : 9);
	}
	
	function onDraw()
	{
		if($this->maximized)
		{
			if(!$this->oldSizeX && !$this->oldSizeY)
			{
				$this->oldSizeX = $this->sizeX;
				$this->oldSizeY = $this->sizeY;
			}
			$this->setSize(320, 180);
			$this->centerOnScreen();
		}
		else
		{
			if($this->oldSizeX || $this->oldSizeY)
			{
				$this->setSize($this->oldSizeX, $this->oldSizeY);
				$this->centerOnScreen();
				$this->oldSizeX = null;
				$this->oldSizeY = null;
			}
		}
	}
	
	/**
	 * Unmaximize window if needed
	 */
	protected function onHide()
	{
		if($this->maximized)
		{
			if ($this->oldSizeX || $this->oldSizeY)
			{
				$this->setSize($this->oldSizeX, $this->oldSizeY);
				$this->centerOnScreen();
				$this->oldSizeX = null;
				$this->oldSizeY = null;
			}
			$this->maximized = false;
		}
	}
	
	function destroy()
	{
		parent::destroy();
		
		$this->buttonMin = null;
		$this->buttonMax = null;
		
		$thumbnail = GuiHandler::getInstance()->getThumbnail($this);
		if($thumbnail)
			$thumbnail->destroy();
	}
}

?>