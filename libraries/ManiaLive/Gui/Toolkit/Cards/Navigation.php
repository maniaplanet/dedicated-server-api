<?php
/**
 * @author Maxime Raoust
 * @copyright 2009-2010 NADEO 
 */

namespace ManiaLive\Gui\Toolkit\Cards;

use ManiaLive\Gui\Toolkit as Toolkit;
use ManiaLive\Gui\Toolkit\Elements as Elements;
use ManiaLive\Gui\Toolkit\Layouts as Layouts;

/**
 * Navigation menu
 * Looks like the navigation menu on the left in the game menus
 */
class Navigation extends Elements\Quad
{
	const BUTTONS_TOP = true;
	const BUTTONS_BOTTOM = false;
	
	/**
	 * @var Label
	 */
	public $title;
	/**
	 * @var Label
	 */
	public $subTitle;
	/**
	 * @var Quad
	 */
	public $titleBg;
	/**
	 * @var Quad
	 */
	public $logo;
	/**
	 * @var NavigationButton
	 */
	public $quitButton;
	/**
	 * @var NavigationButton
	 */
	public $lastItem;
	
	protected $showQuitButton = true;
	protected $items = array();
	protected $bottomItems = array();
	protected $marginHeight = 1;
	protected $yIndex = -10;
	protected $sizeX = 30;
	protected $sizeY = 96;

	function __construct () 
	{	
		$this->setStyle(Toolkit\DefaultStyles::Navigation_Style);
		$this->setSubStyle(Toolkit\DefaultStyles::Navigation_Substyle);
		
		$this->titleBg = new Elements\Quad ($this->sizeX-1, 7);
		$this->titleBg->setStyle(Toolkit\DefaultStyles::Navigation_TitleBg_Style);
		$this->titleBg->setSubStyle(Toolkit\DefaultStyles::Navigation_TitleBg_Substyle);
		
		$this->title = new Elements\Label ($this->sizeX-2.5);
		$this->title->setPosition (1.5, -0.75, 2);
		$this->title->setStyle(Toolkit\DefaultStyles::Navigation_Title_Style);
		
		$this->subTitle = new Elements\Label ($this->sizeX-4);
		$this->subTitle->setPosition (1.5, -4, 3);
		$this->subTitle->setStyle(Toolkit\DefaultStyles::Navigation_Subtitle_Style);
		
		$this->quitButton = new NavigationButton ();
		$this->quitButton->text->setText("Back");
		$this->quitButton->icon->setSubStyle("Back");
		
		$this->logo = new Elements\Icons128x128_1(6);
		$this->logo->setPosition (22.5, -0.5, 2);
		$this->logo->setSubStyle(null);
	}
	
	/**
	 * Adds a navigation button to the menu
	 */
	function addItem($topItem = self::BUTTONS_TOP) 
	{
		$item = new NavigationButton($this->sizeX-1);
		if($topItem)
		{
			$this->items[] = $item;
		}
		else
		{
			$this->bottomItems[] = $item;
		}
		
		$this->lastItem = $item;
	}
	
	/**
	 * Adds a vertical gap before the next item
	 * @param float
	 */
	function addGap($gap = 3) 
	{
		$item = new Elements\Spacer(1, $gap);
		$this->items[] = $item;
	}
	
	/**
	 * Hides the quit/back button
	 */
	function hideQuitButton() 
	{
		$this->showQuitButton = false;
	}
	
	protected function preFilter () 
	{
		Toolkit\Manialink::beginFrame(-64, 48, 1);
	}
	
	protected function postFilter () 
	{
		// Frame was created in preFilter
		// Manialink::beginFrame()
		{
			Toolkit\Manialink::beginFrame($this->posX+0.5, $this->posY-0.5, $this->posZ+1);
			{
				$this->titleBg->save();
				$this->title->save();
				$this->subTitle->save();
				$this->logo->save();
				
				if($this->items)
				{
					$layout = new Layouts\ColumnLayout($this->sizeX-1, $this->sizeY-10);
					$layout->setMarginHeight(1);
					Toolkit\Manialink::beginFrame(0, -10, 0, null, $layout);
					{
						foreach($this->items as $item) 
						{
							$item->save();
						}
						Toolkit\Manialink::endFrame();
					}
				}
				
				if($this->bottomItems)
				{
					$this->bottomItems = array_reverse($this->bottomItems);
					
					$layout = new Layouts\ColumnLayout($this->sizeX-1, $this->sizeY-10);
					$layout->setDirection(ColumnLayout::DIRECTION_UP);
					$layout->setMarginHeight(1);
					Toolkit\Manialink::beginFrame(0, -$this->sizeY+$this->quitButton->getSizeY()+2, 0, null, $layout);
					{
						foreach($this->bottomItems as $item) 
						{
							$item->save();
						}
						Toolkit\Manialink::endFrame();
					}
				}
				
				if($this->showQuitButton) 
				{
					$this->quitButton->setSizeX($this->sizeX-1);
					$this->quitButton->setPosition(0, -$this->sizeY+$this->quitButton->getSizeY()+2);
					$this->quitButton->save();
				}
			}
			Toolkit\Manialink::endFrame();
		}	
		Toolkit\Manialink::endFrame();
	}	
}

?>