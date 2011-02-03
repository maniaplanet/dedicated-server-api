<?php
/**
 * @author Maxime Raoust
 * @copyright 2009-2010 NADEO 
 */

namespace ManiaLive\Gui\Windowing\Controls;

use ManiaLive\Utilities\Console;
use ManiaLive\Gui\Windowing\Control;
use ManiaLib\Gui\Elements as Elements;
use ManiaLib\Gui as Toolkit;

/**
 * Page Navigator
 * Page navigation arrows at the bottom of the lists
 * 
 * @author Maxime Raoust
 */
class PageNavigator extends Control
{
	public $arrowNext;
	public $arrowPrev;
	public $arrowFastNext;
	public $arrowFastPrev;
	public $arrowLast;
	public $arrowFirst;
	public $text;

	public $arrowNoneStyle = Elements\Icons64x64_1::StarGold;
	public $arrowNextStyle = Elements\Icons64x64_1::ArrowNext;
	public $arrowPrevStyle = Elements\Icons64x64_1::ArrowPrev;
	public $arrowFastNextStyle = Elements\Icons64x64_1::ArrowFastNext;
	public $arrowFastPrevStyle = Elements\Icons64x64_1::ArrowFastPrev;
	public $arrowFirstStyle = Elements\Icons64x64_1::ArrowFirst;
	public $arrowLastStyle = Elements\Icons64x64_1::ArrowLast;

	protected $showLast;
	protected $showFastNext;
	protected $showText;
	protected $pageNumber;
	protected $currentPage;

	function initializeComponents()
	{
		$this->sizeX = 15;
		$this->sizeY = 4;
		
		$this->arrowNext = new Elements\Icons64x64_1();
		$this->arrowNext->setSubStyle($this->arrowNoneStyle);
		$this->addComponent($this->arrowNext);
		
		$this->arrowPrev = new Elements\Icons64x64_1();
		$this->arrowPrev->setSubStyle($this->arrowNoneStyle);
		$this->addComponent($this->arrowPrev);
		
		$this->arrowFastNext = new Elements\Icons64x64_1();
		$this->arrowFastNext->setSubStyle($this->arrowNoneStyle);
		$this->addComponent($this->arrowFastNext);
		
		$this->arrowFastPrev = new Elements\Icons64x64_1();
		$this->arrowFastPrev->setSubStyle($this->arrowNoneStyle);
		$this->addComponent($this->arrowFastPrev);
		
		$this->arrowLast = new Elements\Icons64x64_1();
		$this->arrowLast->setSubStyle($this->arrowNoneStyle);
		$this->addComponent($this->arrowLast);
		
		$this->arrowFirst = new Elements\Icons64x64_1();
		$this->arrowFirst->setSubStyle($this->arrowNoneStyle);
		$this->addComponent($this->arrowFirst);
		
		$this->text = new Elements\Label(5);
		$this->addComponent($this->text);
		
		$this->showLast = false;
		$this->showFastNext = false;
		$this->showText = true;
		
		$this->setIconSize();
	}
	
	/**
	 * Sets the size of the navigation icons
	 */
	function setIconSize($iconSize = 5, $nullValue=null)
	{		
		$this->arrowNext->setSize($iconSize, $iconSize);
		$this->arrowPrev->setSize($iconSize, $iconSize);
		$this->arrowFastNext->setSize($iconSize, $iconSize);
		$this->arrowFastPrev->setSize($iconSize, $iconSize);
		$this->arrowLast->setSize($iconSize, $iconSize);
		$this->arrowFirst->setSize($iconSize, $iconSize);
	}
	
	/**
	 * Sets the page number
	 */
	function setPageNumber($pageNumber)
	{
		$this->pageNumber = $pageNumber;
	}
	
	/**
	 * Sets the current page
	 */
	function setCurrentPage($currentPage)
	{
		$this->currentPage = $currentPage;
	}
	
	/**
	 * Shows or hides the "go to first/last" navigation icons
	 */
	function showLast($show = true)
	{
		$this->arrowLast->setVisibility($show);
	}
	
	/**
	 * Returns whether the "go to first/last" navigation icons are shown 
	 */
	function isLastShown()
	{
		return $this->arrowLast->isVisible();
	}
	
	/**
	 * Shows or hides the "fast prev/next" navigation icons
	 */
	function showFastNext($show = true)
	{
		$this->arrowFastNext->setVisibility($show);
	}
	
	/**
	 * Returns whether the "fast prev/next" navigation icons are shown 
	 */
	function isFastNextShown()
	{
		return $this->arrowFastNext->isVisible();
	}
	
	/**
	 * Shows or hides the text. Note that if the current page or the page number
	 * isn't declared, the text won't be shown
	 */
	function showText($show = true)
	{
		$this->text->setVisibility($show);
	}
	
	/**
	 * Returns whether the text is shown
	 */
	function isTextShown()
	{
		return $this->text->isVisible();
	}
	
	/**
	 * Saves the PageNavigator in the GUI objects stack
	 */
	function beforeDraw()
	{
		// Show / hide text
		if(!$this->currentPage || !$this->pageNumber)
		{
			$this->showText(false);
		}
		
		// Auto show fast next / last
		if($this->arrowFirst->hasLink() || $this->arrowLast->hasLink() )
		{
			$this->showLast();
		}
		
		if($this->arrowFastNext->hasLink() || $this->arrowFastPrev->hasLink() )
		{
			$this->showFastNext();
		}
		
		// Arrow styles
		if($this->arrowNext->hasLink())
		{
			$this->arrowNext->setSubStyle($this->arrowNextStyle);
		}
		else
		{
			$this->arrowNext->setSubStyle($this->arrowNoneStyle);
		}
		
		if($this->arrowPrev->hasLink())
		{
			$this->arrowPrev->setSubStyle($this->arrowPrevStyle);
		}
		else
		{
			$this->arrowPrev->setSubStyle($this->arrowNoneStyle);
		}
		
		if($this->arrowNext->hasLink() && $this->arrowFastNext->hasLink())
		{
			$this->arrowFastNext->setSubStyle($this->arrowFastNextStyle);
		}
		else
		{
			$this->arrowFastNext->setManialink(null);
		}
		
		if($this->arrowPrev->hasLink() && $this->arrowFastPrev->hasLink())
		{
			 $this->arrowFastPrev->setSubStyle($this->arrowFastPrevStyle);
		}
		else
		{
			$this->arrowFastNext->setManialink(null);
		} 
		
		if($this->arrowNext->hasLink() && $this->arrowLast->hasLink())
		{
			 $this->arrowLast->setSubStyle($this->arrowLastStyle);
		}
		else
		{
			$this->arrowLast->setSubStyle($this->arrowNoneStyle);
		}
		
		if($this->arrowPrev->hasLink() && $this->arrowFirst->hasLink())
		{
			 $this->arrowFirst->setSubStyle($this->arrowFirstStyle);
		}
		else 
		{
			$this->arrowFirst->setSubStyle($this->arrowNoneStyle);
		}

		// Text
		$this->text->setStyle("TextStaticSmall");
		$this->text->setText($this->currentPage . "/" . $this->pageNumber);

		// Positioning in relation to the center of the containing frame
		$this->text->setAlign("center", "center");
		$this->text->setPositionZ(1);

		$this->arrowNext->setValign("center");
		$this->arrowFastNext->setValign("center");
		$this->arrowLast->setValign("center");

		$this->arrowNext->setPosition(($this->text->getSizeX() / 2) + 1, 0);
		$this->arrowFastNext->setPosition($this->arrowNext->getPosX() + $this->arrowNext->getSizeX(), 0);
		$this->arrowLast->setPosition(
			$this->arrowNext->getPosX() + 
			(int)$this->showFastNext*$this->arrowFastNext->getSizeX() + 
			$this->arrowNext->getSizeX(), 
			0);

		$this->arrowPrev->setAlign("right", "center");
		$this->arrowFastPrev->setAlign("right", "center");
		$this->arrowFirst->setAlign("right", "center");

		$this->arrowPrev->setPosition(-($this->text->getSizeX()/2) - 1, 0);
		$this->arrowFastPrev->setPosition($this->arrowPrev->getPosX() - $this->arrowPrev->getSizeX(), 0);
		$this->arrowFirst->setPosition(
			$this->arrowPrev->getPosX() -
			(int)$this->showFastNext*$this->arrowFastPrev->getSizeX() - 
			$this->arrowPrev->getSizeX(),
			0);
	}
	
	function afterDraw() {}
}

?>