<?php

namespace ManiaLivePlugins\Standard\Dedimania\Gui\Controls;

use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\Bgs1InRace;

class LeaderboardCell extends \ManiaLive\Gui\Windowing\Control
{
	protected $background;
	protected $label;
	protected $highlight;
	
	function initializeComponents()
	{
		$this->sizeX = $this->getParam(0);
		$this->sizeY = $this->getParam(1);
		
		// insert background ...
		$this->background = new Bgs1InRace($this->getSizeX(), $this->getSizeY());
		$this->addComponent($this->background);
		
		// insert label ...
		$this->label = new Label($this->getSizeX() - 2, $this->getSizeY());
		$this->label->setPosition(1, 1);
		$this->addComponent($this->label);
	}
	
	function onResize()
	{
		$this->background->setSize($this->getSizeX(), $this->getSizeY());
		$this->label->setSize($this->getSizeX() - 2, $this->getSizeY());
	}
	
	function beforeDraw()
	{
		if ($this->highlight)
			$style = Bgs1InRace::NavButtonBlink;
		else
			$style = Bgs1InRace::NavButton;
			
		$this->background->setSubStyle($style);
	}
	
	function setHighlight($highlight)
	{
		$this->highlight = $highlight;
	}
	
	function setText($text)
	{
		$this->label->setText($text);
	}
}