<?php

namespace ManiaLivePlugins\Standard\Menubar\Gui\Controls;

use ManiaLive\Gui\Windowing\Container;
use ManiaLib\Gui\Layouts\Column;
use ManiaLib\Gui\Elements\Bgs1;
use ManiaLib\Gui\Elements\Icons128x128_1;
use ManiaLib\Gui\Elements\BgsPlayerCard;
use ManiaLib\Gui\Elements\Label;

class Subitem extends \ManiaLive\Gui\Windowing\Control
{
	private $name;
	private $label;
	private $background;
	private $action;
	
	function initializeComponents()
	{
		$this->name = $this->getParam(0, '');
		$this->sizeX = $this->getParam(1, 30);
		$this->sizeY = $this->getParam(2, 6);
		
		$this->action = array();
		
		$this->background = new Bgs1();
		$this->background->setSize($this->getSizeX(), $this->getSizeY());
		$this->background->setSubStyle(Bgs1::BgListLine);
		$this->addComponent($this->background);
		
		$this->label = new Label();
		$this->label->setStyle(Label::TextCardSmallScores2Rank);
		$this->addComponent($this->label);
	}
	
	function beforeDraw()
	{
		$this->label->setValign('center2');
		$this->label->setText('$i'.$this->name);
		$this->label->setPositionY($this->getSizeY() / 2 + 0.5);
		$this->label->setHalign('left');
		$this->label->setPositionX(1);
		
		$this->background->setAction($this->callback('onClick'));
		$this->background->setSubStyle(Bgs1::NavButtonBlink);
	}
	
	function afterDraw() {}
	
	function setName($name)
	{
		$this->name = $name;
	}
	
	function getName()
	{
		return $this->name;
	}
	
	function setAction($action)
	{
		$this->action = $action;
	}
	
	function onClick($login)
	{
		if (is_callable($this->action))
		{
			if ($this->getPlayerValue('active'))
			{
				$this->getPlayerValue('active')->toggleSubitems($login);
				call_user_func_array($this->action, array($login));
			}
		}
	}
	
	function destroy()
	{
		$this->action = null;
		parent::destroy();
	}
}