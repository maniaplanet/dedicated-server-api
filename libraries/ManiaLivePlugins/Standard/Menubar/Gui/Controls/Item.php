<?php
/**
 * Menubar Plugin - Handle dynamically a menu
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\Standard\Menubar\Gui\Controls;

use ManiaLib\Gui\Layouts\Column;
use ManiaLib\Gui\Elements\Bgs1;
use ManiaLib\Gui\Elements\Icons128x128_1;
use ManiaLib\Gui\Elements\Label;
use ManiaLive\Gui\Controls\Frame;
use ManiaLive\Gui\ActionHandler;

class Item extends \ManiaLive\Gui\Control
{
	private $background;
	private $icon;
	private $container;
	private $subitems = array();
	
	function __construct($name = '', $sizeX = 30, $sizeY = 6)
	{
		$this->sizeX = $sizeX;
		$this->sizeY = $sizeY;

		$this->container = new Frame(-$sizeX, 0, new Column(0, 0, Column::DIRECTION_DOWN));
		$this->container->setVisibility(false);
		$this->addComponent($this->container);
		
		$this->background = new Bgs1();
		$this->background->setSize($sizeX, $sizeY);
		$this->background->setSubStyle(Bgs1::NavButton);
		$this->addComponent($this->background);
		
		$this->icon = new Icons128x128_1();
		$this->icon->setVisibility(false);
		$this->icon->setValign('center');
		$this->icon->setPosition(0.3, -$sizeY / 2);
		$this->addComponent($this->icon);
		
		$label = new Label();
		$label->setStyle(Label::TextCardSmallScores2Rank);
		$label->setAlign('right', 'center2');
		$label->setPosition($sizeX - 2, -$sizeY / 2);
		$label->setText($name);
		$this->addComponent($label);
	}
	
	function setAction($action)
	{
		$this->background->setAction($action);
	}
	
	function setIcon($icon)
	{
		$this->icon->setVisibility($icon == null ? false : true);
		$this->icon->setSubstyle($icon);
	}
	
	function addSubitem($name, $callback)
	{
		if(!is_callable($callback))
			return;
		
		$action = ActionHandler::getInstance()->createAction($callback);
		$this->actions[] = $action;
		$item = new Subitem($name);
		$item->setAction($action);
		$this->container->addComponent($item);
		$this->subitems[] = $item;
	}
	
	function hasSubitems()
	{
		return !empty($this->subitems);
	}
	
	function showSubitems()
	{
		$this->container->setVisibility(true);
		$this->background->setSubStyle(Bgs1::NavButtonBlink);
		$this->icon->setPosX(-3.5);
		$this->icon->setSize(9, 9);
		$this->redraw();
	}
	
	function hideSubitems()
	{
		$this->container->setVisibility(false);
		$this->background->setSubStyle(Bgs1::BgListLine);
		$this->icon->setPosX(.3);
		$this->icon->setSize(7, 7);
		$this->redraw();
	}
	
	function toggleSubitems()
	{
		if($this->container->isVisible())
			$this->hideSubitems();
		else
			$this->showSubitems();
	}
	
	function destroy()
	{
		$this->subitems = array();
		$this->container->destroy();
		parent::destroy();
	}
}

?>