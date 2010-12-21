<?php

namespace ManiaLive\Gui\Windowing\Controls;

use ManiaLive\Gui\Toolkit\Elements\Bgs1InRace;

use ManiaLive\Gui\Toolkit\Elements\Label;
use ManiaLive\Gui\Toolkit\Elements\Button;

class ButtonResizeable extends \ManiaLive\Gui\Windowing\Control
{
	private $button;
	private $label;
	
	function initializeComponents()
	{
		$this->button = new Bgs1InRace();
		$this->button->setSubStyle(Bgs1InRace::BgButton);
		$this->addComponent($this->button);
		
		$this->label = new Label();
		$this->label->setValign('center');
		$this->label->setHalign('center');
		$this->label->setTextColor('000');
		$this->addComponent($this->label);
	}
	
	function beforeDraw()
	{
		$this->button->setSize($this->getSizeX(), $this->getSizeY());
		
		$this->label->setTextSize($this->getSizeY() - 2);
		$this->label->setSize($this->getSizeX() - 3, $this->getSizeY() - 1);
		$this->label->setPosition($this->getSizeX() / 2, $this->getSizeY() / 2);
	}
	
	function afterDraw() {}
	
	function getText()
	{
		return $this->label->getText();
	}
	
	function setText($text)
	{
		$this->label->setText($text);
	}
	
	function setAction($action)
	{
		$this->button->setAction($action);
	}
	
	function setUrl($url)
	{
		$this->button->setUrl($url);
	}
	
	function setManialink($manialink)
	{
		$this->button->setManialink($manialink);
	}
}

?>