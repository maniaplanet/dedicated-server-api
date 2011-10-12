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

namespace ManiaLivePlugins\Standard\Profiler\Gui\Controls;

use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\Bgs1InRace;

class ListItem extends \ManiaLive\Gui\Windowing\Control
{
	public $background;
	public $label;
	
	function initializeComponents()
	{
		$this->sizeY = $this->getParam(0, 6);
		
		$this->background = new Bgs1InRace();
		$this->background->setSubStyle(Bgs1InRace::BgCardChallenge);
		$this->background->setSizeY(6);
		$this->addComponent($this->background);
		
		$this->label = new Label();
		$this->label->setTextColor('000');
		$this->label->setPosition(2, 1);
		$this->label->setSizeY(2);
		$this->label->setTextSize(2);
		$this->addComponent($this->label);
	}
	
	function onResize()
	{
		$this->background->setSizeX($this->sizeX);
		$this->label->setSizeX($this->sizeX - 2);
	}
}

?>