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

namespace ManiaLive\Gui\Windows;

use ManiaLib\Gui\Elements\Bgs1InRace;
use ManiaLib\Gui\Elements\Icons128x128_Blink;
use ManiaLib\Gui\Elements\Icons64x64_1;
use ManiaLib\Gui\Elements\Label;
use ManiaLive\Gui\Controls\Frame;

/**
 * Draws miniature image of a window.
 *
 * @author Florian Schnell
 */
final class Thumbnail extends \ManiaLive\Gui\Window
{
	private $window;
	private $isHighlighted;
	
	private $windowContent;
	private $border;
	private $buttonClose;
	private $buttonCloseBg;
	private $highlight;
	
	protected function onConstruct($window = null)
	{
		$this->window = $window;
		$this->isHighlighted = false;
		
		$this->highlight = new Icons128x128_Blink();
		$this->highlight->setSubStyle(Icons128x128_Blink::ShareBlink);
		$this->addComponent($this->highlight);
		
		$this->border = new Bgs1InRace();
		$this->border->setSubStyle(Bgs1InRace::BgTitleShadow);
		$this->border->setAction($window->createAction(array($window, 'show')));
		$this->addComponent($this->border);
		
		$this->windowContent = new Frame();
		$this->windowContent->setAlign('center', 'center');
		$this->windowContent->setSize($window->getSizeX(), $window->getSizeY());
		foreach($window->getComponents() as $component)
			$this->windowContent->addComponent($component);
		$this->windowContent->disableLinks();
		$this->addComponent($this->windowContent);
		
		$this->buttonCloseBg = new Bgs1InRace(4, 4);
		$this->buttonCloseBg->setSubStyle(Bgs1InRace::BgTitleShadow);
		$this->buttonCloseBg->setAlign('center', 'center');
		$this->buttonCloseBg->setAction($this->createAction(array($this, 'hide')));
		$this->addComponent($this->buttonCloseBg);
		
		$this->buttonClose = new Icons64x64_1(4);
		$this->buttonClose->setAlign('center', 'center');
		$this->buttonClose->setSubStyle(Icons64x64_1::QuitRace);
		$this->addComponent($this->buttonClose);
	}
	
	function getWindow()
	{
		return $this->window;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Gui/Windowing/ManiaLive\Gui\Windowing.Window::onDraw()
	 */
	function onDraw()
	{
		$this->highlight->setVisibility($this->isHighlighted);
	}
	
	protected function onResize($oldX, $oldY)
	{
		// button and border repositionning
		$this->buttonClose->setPosition($this->sizeX - 2, 2 - $this->sizeY);
		$this->buttonCloseBg->setPosition($this->sizeX - 2, 2 - $this->sizeY);
		$this->windowContent->setPosition($this->sizeX / 2, -$this->sizeY / 2);
		$this->highlight->setSize($this->sizeX, $this->sizeY);
		$this->border->setSize($this->sizeX, $this->sizeY);
		
		// scale the window
		$factorX = ($this->sizeX - 2) / $this->window->getSizeX();
		$factorY = ($this->sizeY - 2) / $this->window->getSizeY();
		$this->windowContent->setScale(min($factorX, $factorY));
	}
	
	function destroy()
	{
		$this->window = null;
		$this->windowContent->clearComponents();
		parent::destroy();
	}
	
	function enableHighlight()
	{
		$this->isHighlighted = true;
		$this->redraw();
	}
	
	function disableHighlight()
	{
		$this->isHighlighted = false;
		$this->redraw();
	}
}

?>