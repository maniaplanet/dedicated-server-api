<?php

namespace ManiaLivePlugins\Standard\Menubar\Gui\Controls;

use ManiaLive\Gui\Windowing\Controls\Frame;
use ManiaLib\Gui\Layouts\Column;
use ManiaLib\Gui\Elements\Bgs1;
use ManiaLib\Gui\Elements\Icons128x128_1;
use ManiaLib\Gui\Elements\BgsPlayerCard;
use ManiaLib\Gui\Elements\Label;

class Item extends \ManiaLive\Gui\Windowing\Control
{
	private $label;
	private $background;
	private $container;
	private $subitems;
	private $name;
	private $icon;
	private $action;
	
	function initializeComponents()
	{
		$this->name = $this->getParam(0, '');
		$this->sizeX = $this->getParam(1, 18);
		$this->sizeY = $this->getParam(2, 4);
		$this->action = null;
		
		$this->subitems = array();

		$this->container = new Frame(0, 0, new Column(0, 0, Column::DIRECTION_UP));
		$this->addComponent($this->container);
		
		$this->background = new Bgs1();
		$this->background->setSize($this->getSizeX(), $this->getSizeY());
		$this->background->setSubStyle(Bgs1::NavButton);
		$this->addComponent($this->background);
		
		$this->icon = new Icons128x128_1();
		$this->icon->setVisibility(false);
		$this->icon->setValign('center');
		$this->addComponent($this->icon);
		
		$this->label = new Label();
		$this->label->setText($this->name);
		$this->label->setHalign('right');
		$this->label->setValign('center');
		$this->label->setStyle(Label::TextCardSmallScores2Rank);
		$this->addComponent($this->label);
	}
	
	function hasAction()
	{
		return ($this->action !== null);
	}
	
	function beforeDraw()
	{
		if ($this->action !== null)
		{
			$this->label->setText('$i'.$this->name);
		}
		else
		{
			$this->label->setText($this->name);
		}
		
		$this->label->setPositionY($this->getSizeY() / 2 - 0.2);
		$this->label->setPositionX($this->getSizeX() - 2);
		
		$this->background->setAction($this->callback('doAction'));
		
		if ($this == $this->getPlayerValue('active'))
		{
			$this->background->setSubStyle(Bgs1::NavButtonBlink);
			$this->icon->setPosition(-3.5);
			$this->icon->setSize(7, 7);
		}
		else
		{
			$this->background->setSubStyle(Bgs1::BgListLine);
			$this->icon->setSize(4, 4);
			$this->icon->setPosition(0.3, $this->getSizeY() / 2);
		}
		
		$this->container->setPosition(-$this->getSizeX(), $this->getSizeY());
		$this->container->clearComponents();
		
		foreach ($this->subitems as $item)
		{
			$this->container->addComponent($item);
		}
	}
	
	function doAction($login)
	{
		if ($this->action == null)
		{
			$this->toggleSubitems($login);
		}
		else
		{
			call_user_func($this->action, $login);
		}
	}
	
	function afterDraw() {}
	
	function setAction($action)
	{
		$this->action = $action;
	}
	
	function setName($name)
	{
		$this->name = $name;
	}
	
	function getName()
	{
		return $this->name;
	}
	
	function setIcon($icon)
	{
		$this->icon->setVisibility($icon == null ? false : true);
		$this->icon->setSubstyle($icon);
	}
	
	function getIcon()
	{
		return $this->icon;
	}
	
	function toggleSubitems($login)
	{
		if ($this->getPlayerValue('active') == $this)
		{
			$this->setPlayerValue('active', null);
			$this->hideSubitems();
		}
		else
		{
			if ($this->getPlayerValue('active') != null)
				$this->getPlayerValue('active')->hideSubitems();
			$this->setPlayerValue('active', $this);
			$this->showSubitems();
		}
		
		$this->getWindow()->show();
	}
	
	function hideSubitems()
	{
		foreach ($this->subitems as $item)
		{
			$item->setVisibility(false);
		}
	}
	
	function showSubitems()
	{
		foreach ($this->subitems as $item)
		{
			$item->setVisibility(true);
		}
	}
	
	function getItem($name)
	{
		if (isset($this->subitems[$name]))
		{
			return $this->subitems[$name];
		}
		else
		{
			return null;
		}
	}
	
	function hasSubitems()
	{
		return (!empty($this->subitems));
	}
	
	function addSubitem(Subitem $item)
	{
		$item->setVisibility(false);
		$this->subitems[$item->getName()] = $item;
	}
	
	function destroy()
	{
		$this->subitems = array();
		parent::destroy();
	}
}

?>