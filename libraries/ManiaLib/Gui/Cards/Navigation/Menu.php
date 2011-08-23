<?php
/**
 * ManiaLib - Lightweight PHP framework for Manialinks
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLib\Gui\Cards\Navigation;

use ManiaLib\Gui\Manialink;
use ManiaLib\Gui\Elements\Quad;
use ManiaLib\Gui\Elements\Bgs1;
use ManiaLib\Gui\Elements\Icons128x128_1;
use \ManiaLib\Gui\Elements\Icons64x64_1;
use ManiaLib\Gui\Elements\Label;
use \ManiaLib\Gui\DefaultStyles;

/**
 * Navigation menu
 * Looks like the navigation menu on the left in the game menus
 */
class Menu extends \ManiaLib\Gui\Elements\Quad
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
	 * @var \ManiaLib\Gui\Elements\Quad
	 */
	public $titleBg;

	/**
	 * @var \ManiaLib\Gui\Elements\Quad
	 */
	public $logo;

	/**
	 * @var Button
	 */
	public $quitButton;

	/**
	 * @var Button
	 */
	public $lastItem;

	/*	 * #@+
	 * @ignore
	 */
	protected $showQuitButton = true;
	protected $items = array();
	protected $bottomItems = array();
	protected $marginHeight = 1;
	protected $yIndex = -10;
	protected $sizeX = 70;
	protected $sizeY = 180;
	/*	 * #@- */

	function __construct()
	{
		$this->setStyle(Quad::Bgs1);
		$this->setSubStyle(Bgs1::BgWindow1);

		$this->titleBg = new Quad($this->sizeX - 0.5, 62);
		$this->titleBg->setPosY(5);
		$this->titleBg->setStyle(DefaultStyles::Navigation_TitleBg_Style);
		$this->titleBg->setSubStyle(DefaultStyles::Navigation_TitleBg_Substyle);

		$this->title = new Label($this->sizeX / 2);
		$this->title->setPosition(10, -41, 2);
		$this->title->setStyle(DefaultStyles::Navigation_Title_Style);

		$this->subTitle = new Label($this->sizeX / 2);
		$this->subTitle->setPosition(10, -48, 3);
		$this->subTitle->setStyle(DefaultStyles::Navigation_Subtitle_Style);

		$this->quitButton = new Button();
		$this->quitButton->text->setText("Back");
		$this->quitButton->text->setStyle(Label::TextButtonNavBack);
		$this->quitButton->icon->setPosition(-8.5, -0.5, 6);
		$this->quitButton->icon->setStyle(Quad::Icons128x128_1);
		$this->quitButton->icon->setSubStyle(Icons128x128_1::BackFocusable);
		$this->quitButton->icon->setSize(11, 11);

		$this->logo = new Icons128x128_1(15);
		$this->logo->setPosition(47, -39, 2);
		$this->logo->setSubStyle(null);
	}

	/**
	 * Adds a navigation button to the menu
	 */
	function addItem($topItem = self::BUTTONS_TOP)
	{
		$item = new Button($this->sizeX - 1);
		$item->setSubStyle(Bgs1::BgEmpty);
		if($topItem)
			$this->items[] = $item;
		else
			$this->bottomItems[] = $item;

		$this->lastItem = $item;
	}

	/**
	 * Return a reference of the last added item
	 * @deprecated use self::$lastItem instead (better performance)
	 * @return Button (ref)
	 */
	function lastItem()
	{
		return $this->lastItem;
	}

	/**
	 * Adds a vertical gap before the next item
	 * @param float
	 */
	function addGap($gap = 3)
	{
		$item = new \ManiaLib\Gui\Elements\Spacer(1, $gap);
		$this->items[] = $item;
	}

	/**
	 * Hides the quit/back button
	 */
	function hideQuitButton()
	{
		$this->showQuitButton = false;
	}

	/**
	 * @ignore
	 */
	protected function preFilter()
	{
		\ManiaLib\Gui\Manialink::beginFrame(-150, 90, 1);
	}

	/**
	 * @ignore
	 */
	protected function postFilter()
	{
		// Frame was created in preFilter
		// \ManiaLib\Gui\Manialink::beginFrame()
		{
			\ManiaLib\Gui\Manialink::beginFrame($this->posX + 0.25, $this->posY,
				$this->posZ + 1);
			{
				$this->titleBg->save();
				$this->title->save();
				$this->subTitle->save();
				$this->logo->save();

				if($this->items)
				{
					$layout = new \ManiaLib\Gui\Layouts\Column($this->sizeX - 1, $this->sizeY - 10);
					$layout->setMarginHeight(3.5);
					\ManiaLib\Gui\Manialink::beginFrame(0, -73, 0, null, $layout);
					{
						foreach($this->items as $item)
						{
							$item->save();
						}
						\ManiaLib\Gui\Manialink::endFrame();
					}
				}

				if($this->bottomItems)
				{
					$this->bottomItems = array_reverse($this->bottomItems);

					$layout = new \ManiaLib\Gui\Layouts\Column($this->sizeX - 1, $this->sizeY - 10);
					$layout->setDirection(\ManiaLib\Gui\Layouts\Column::DIRECTION_UP);
					$layout->setMarginHeight(1);
					\ManiaLib\Gui\Manialink::beginFrame(0,
						-$this->sizeY + $this->quitButton->getSizeY() + 15, 0, null, $layout);
					{
						foreach($this->bottomItems as $item)
						{
							$item->save();
						}
						\ManiaLib\Gui\Manialink::endFrame();
					}
				}

				if($this->showQuitButton)
				{
					$this->quitButton->setSizeX($this->sizeX - 1);
					$this->quitButton->setPosition(-1,
						-$this->sizeY + $this->quitButton->getSizeY() + 8);
					$this->quitButton->save();
				}
			}
			\ManiaLib\Gui\Manialink::endFrame();
		}
		\ManiaLib\Gui\Manialink::endFrame();
	}

}

?>