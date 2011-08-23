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

namespace ManiaLive\Gui\Windowing\Windows;

use ManiaLib\Gui\Elements\Icons128x128_Blink;
use ManiaLib\Gui\Elements\Icons128x128_1;
use ManiaLib\Gui\Elements\Icons64x64_1;
use ManiaLib\Gui\Elements\Bgs1InRace;
use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\BgsChallengeMedals;
use ManiaLive\Gui\Windowing\Controls\Frame;
use ManiaLib\Gui\Elements\BgsPlayerCard;

/**
 * Draws miniature image of a window.
 *
 * @author Florian Schnell
 */
class Thumbnail extends \ManiaLive\Gui\Windowing\Window
{
	/**
	 * @var \ManiaLive\Gui\Windowing\Window
	 */
	public $window;
	public $windowContent;
	protected $highlight;
	protected $border;
	protected $btnClose;
	protected $btnCloseBg;
	protected $bgHighlight;
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Gui/Windowing/ManiaLive\Gui\Windowing.Window::initializeComponents()
	 */
	function initializeComponents()
	{
		$this->highlight = false;
		
		$this->bgHighlight = new Icons128x128_Blink();
		$this->bgHighlight->setSubStyle(Icons128x128_Blink::ShareBlink);
		$this->addComponent($this->bgHighlight);
		
		$this->border = new Bgs1InRace();
		$this->border->setSubStyle(Bgs1InRace::BgTitleShadow);
		$this->addComponent($this->border);
		
		$this->windowContent = new Frame();
		$this->windowContent->disableLinks();
		$this->addComponent($this->windowContent);
		
		$this->btnCloseBg = new BgsPlayerCard();
		$this->btnCloseBg->setStyle(Bgs1InRace::Bgs1InRace);
		$this->btnCloseBg->setSubStyle(Bgs1InRace::BgTitleShadow);
		$this->btnCloseBg->setAlign('center', 'center');
		$this->btnCloseBg->setAction($this->callback('hide'));
		$this->addComponent($this->btnCloseBg);
		
		$this->btnClose = new Icons64x64_1();
		$this->btnClose->setAlign('center', 'center');
		$this->btnClose->setSubStyle(Icons64x64_1::QuitRace);
		$this->addComponent($this->btnClose);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Gui/Windowing/ManiaLive\Gui\Windowing.Window::onShow()
	 */
	function onDraw()
	{
		$this->bgHighlight->setVisibility($this->highlight);
		$this->border->setAction($this->callback(array($this->window, 'show')));
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLib/Gui/ManiaLib\Gui.Component::onResize()
	 */
	function onResize()
	{
		$this->setThumbSize($this->getSizeX(), $this->getSizeY());
	}
	
	/**
	 * Calculates sizes for all elements.
	 * 
	 * @param $sizeX
	 * @param $sizeY
	 */
	protected function setThumbSize($sizeX, $sizeY)
	{
		// calculate window miniature size
		$sizeXWindow = $sizeX - 2;
		$sizeYWindow = $sizeY - 2;
		
		// calculate stretching factor and decide which to use
		$factorX = 1/($sizeXWindow / $this->window->getSizeX());
		$factorY = 1/($sizeYWindow / $this->window->getSizeY());
		
		if ($factorX > $factorY)
		{
			$factor = $factorX;
		}
		else
		{
			$factor = $factorY;
		}
		
		// close button positioning and resizing
		$this->btnClose->setSize(2.5 * $factor, 2.5 * $factor);
		$this->btnClose->setPosition(18.7 * $factor, 12.7 * $factor);
		
		// background for the close button
		$this->btnCloseBg->setSize(2.5 * $factor, 2.5 * $factor);
		$this->btnCloseBg->setPosition(18.7 * $factor, 12.7 * $factor);
		
		$this->bgHighlight->setSize($sizeX * $factor, $sizeY * $factor);
		
		// resize border element
		$this->border->setSize($sizeX * $factor, $sizeY * $factor);
		
		// position the window miniature ...
		if ($factorX > $factorY)
		{
			$this->windowContent->setPosition($factor, $factor + ($sizeYWindow * $factor - $sizeYWindow * $factorY) / 2);
		}
		else
		{
			$this->windowContent->setPosition($factor + ($sizeXWindow * $factor - $sizeXWindow * $factorX) / 2, $factor);
		}
		
		// scale the window
		$this->setScale(1/$factor);
	}
	
	function destroy()
	{
		$this->window = null;
		$this->windowContent->clearComponents();
		parent::destroy();
	}
	
	function enableHighlight()
	{
		$this->highlight = true;
		$this->show();
	}
	
	function disableHighlight()
	{
		$this->highlight = false;
		$this->show();
	}
	
	/**
	 * This creates a thumbnail from an existing window.
	 * Taking all its elements, rebuild them, add new
	 * elements like background and close button.
	 * Then scale it to the specified size.
	 * 
	 * @param \ManiaLive\Gui\Windowing\Window $window
	 */
	static function fromWindow(\ManiaLive\Gui\Windowing\Window $window)
	{
		$thumb = Thumbnail::Create($window->getRecipient(), false);
		
		$window->onDraw();
		
		$components = $window->getComponents();
		foreach ($components as $component)
		{
			$thumb->windowContent->addComponent($component);
		}
		
		$thumb->window = $window;
		
		return $thumb;
	}
}

?>