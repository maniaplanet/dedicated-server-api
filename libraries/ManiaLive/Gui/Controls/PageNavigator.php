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

namespace ManiaLive\Gui\Controls;

use ManiaLib\Gui\Elements\Bgs1;
use ManiaLib\Gui\Elements\Icons64x64_1;
use ManiaLib\Gui\Elements\Label;

/**
 * Page Navigator
 * Page navigation arrows at the bottom of the lists
 * 
 * @author Maxime Raoust
 */
class PageNavigator extends \ManiaLive\Gui\Control
{
	public $arrowNext;
	public $arrowPrev;
	public $arrowFastNext;
	public $arrowFastPrev;
	public $arrowLast;
	public $arrowFirst;
	public $text;
	public $textBg;

	public $arrowNoneStyle = Icons64x64_1::ClipPause;
	public $arrowNextStyle = Icons64x64_1::ArrowNext;
	public $arrowPrevStyle = Icons64x64_1::ArrowPrev;
	public $arrowFastNextStyle = Icons64x64_1::ArrowFastNext;
	public $arrowFastPrevStyle = Icons64x64_1::ArrowFastPrev;
	public $arrowFirstStyle = Icons64x64_1::ArrowFirst;
	public $arrowLastStyle = Icons64x64_1::ArrowLast;

	protected $showLast;
	protected $showFastNext;
	protected $showText;
	protected $pageNumber;
	protected $currentPage;
	
	function __construct($iconSize=8)
	{
		$this->sizeX = $iconSize * 4.5;
		$this->sizeY = $iconSize;
		
		$this->arrowNext = new Icons64x64_1($iconSize);
		$this->arrowPrev = new Icons64x64_1($iconSize);
		$this->arrowFastNext = new Icons64x64_1($iconSize);
		$this->arrowFastPrev = new Icons64x64_1($iconSize);
		$this->arrowLast = new Icons64x64_1($iconSize);
		$this->arrowFirst = new Icons64x64_1($iconSize);
		$this->text = new Label(2*$iconSize - 2);
		$this->textBg = new Bgs1(2*$iconSize, $iconSize-2);
		
		$this->showLast = false;
		$this->showFastNext = false;
		$this->showText = true;
		
		$this->arrowNext->setSubStyle($this->arrowNoneStyle);
		$this->arrowPrev->setSubStyle($this->arrowNoneStyle);
		$this->arrowFastNext->setSubStyle($this->arrowNoneStyle);
		$this->arrowFastPrev->setSubStyle($this->arrowNoneStyle);
		$this->arrowLast->setSubStyle($this->arrowNoneStyle);
		$this->arrowFirst->setSubStyle($this->arrowNoneStyle);
		
		$this->addComponent($this->arrowNext);
		$this->addComponent($this->arrowPrev);
		$this->addComponent($this->arrowFastNext);
		$this->addComponent($this->arrowFastPrev);
		$this->addComponent($this->arrowLast);
		$this->addComponent($this->arrowFirst);
		$this->addComponent($this->textBg);
		$this->addComponent($this->text);
	}
	
	/**
	 * Sets the size of the navigation icons
	 */
	function setSize($iconSize = 8)
	{
		$this->sizeX = $iconSize * 4.5;
		$this->sizeY = $iconSize;
		
		$this->arrowNext->setSize($iconSize, $iconSize);
		$this->arrowPrev->setSize($iconSize, $iconSize);
		$this->arrowFastNext->setSize($iconSize, $iconSize);
		$this->arrowFastPrev->setSize($iconSize, $iconSize);
		$this->arrowLast->setSize($iconSize, $iconSize);
		$this->arrowFirst->setSize($iconSize, $iconSize);
		$this->text = new Label(2*$iconSize - 2);
		$this->textBg = new Bgs1(2*$iconSize, $iconSize-2);
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
		$this->showLast = $show;
	}
	
	/**
	 * Returns whether the "go to first/last" navigation icons are shown 
	 */
	function isLastShown()
	{
		return $this->showLast;
	}
	
	/**
	 * Shows or hides the "fast prev/next" navigation icons
	 */
	function showFastNext($show = true)
	{
		$this->showFastNext = $show;
	}
	
	/**
	 * Returns whether the "fast prev/next" navigation icons are shown 
	 */
	function isFastNextShown()
	{
		return $this->showFastNext;
	}
	
	/**
	 * Shows or hides the text. Note that if the current page or the page number
	 * isn't declared, the text won't be shown
	 */
	function showText($show = true)
	{
		$this->showText = $show;
	}
	
	/**
	 * Returns whether the text is shown
	 */
	function isTextShown()
	{
		return $this->showText;
	}
	
	/**
	 * Saves the PageNavigator in the GUI objects stack
	 */
	function onDraw()
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
		if($this->arrowPrev->hasLink())
		{
			 $this->arrowPrev->setSubStyle($this->arrowPrevStyle);
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
			$this->arrowFastPrev->setManialink(null);
		}
		if($this->arrowNext->hasLink() && $this->arrowLast->hasLink())
		{
			 $this->arrowLast->setSubStyle($this->arrowLastStyle);
		}
		else
		{
			$this->arrowLast->setManialink(null);
		}
		if($this->arrowPrev->hasLink() && $this->arrowFirst->hasLink())
		{
			 $this->arrowFirst->setSubStyle($this->arrowFirstStyle);
		}
		else
		{
			$this->arrowFirst->setManialink(null);
		}

		// Text
		$this->text->setStyle(Label::TextValueSmall);
		$this->text->setText($this->currentPage . ' / ' . $this->pageNumber);

		// Positioning in relation to the center of the containing frame
		$this->text->setAlign('center', 'center2');
		$this->text->setPosZ(1);
		
		$this->textBg->setSubStyle(Bgs1::BgPager);
		$this->textBg->setAlign('center', 'center');

		$this->arrowNext->setValign('center');
		$this->arrowFastNext->setValign('center');
		$this->arrowLast->setValign('center');

		$this->arrowNext->setPosition(($this->text->getSizeX() / 2) + 1, 0);
		$this->arrowFastNext->setPosition($this->arrowNext->getPosX() + $this->arrowNext->getSizeX(), 0);
		$this->arrowLast->setPosition(
			$this->arrowNext->getPosX() +
			(int)$this->showFastNext*$this->arrowFastNext->getSizeX() + 
			$this->arrowNext->getSizeX(), 
			0);

		$this->arrowPrev->setAlign('right', 'center');
		$this->arrowFastPrev->setAlign('right', 'center');
		$this->arrowFirst->setAlign('right', 'center');

		$this->arrowPrev->setPosition(-($this->text->getSizeX()/2) - 1, 0);
		$this->arrowFastPrev->setPosition($this->arrowPrev->getPosX() - $this->arrowPrev->getSizeX(), 0);
		$this->arrowFirst->setPosition(
			$this->arrowPrev->getPosX() -
			(int)$this->showFastNext*$this->arrowFastPrev->getSizeX() - 
			$this->arrowPrev->getSizeX(),
			0);
	}
}

?>