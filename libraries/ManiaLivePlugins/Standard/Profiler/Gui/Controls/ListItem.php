<?php
/**
 * Profiler Plugin - Show statistics about ManiaLive
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

class ListItem extends \ManiaLive\Gui\Control
{
	public $background;
	public $label;
	
	function __construct($sizeY=6)
	{
		$this->background = new Bgs1InRace();
		$this->background->setSubStyle(Bgs1InRace::BgCardChallenge);
		$this->addComponent($this->background);
		
		$this->label = new Label();
		$this->label->setTextColor('000');
		$this->label->setTextSize(2);
		$this->label->setValign('center');
		$this->addComponent($this->label);
		
		$this->setSizeY($sizeY);
	}
	
	function onResize($oldX, $oldY)
	{
		$this->background->setSize($this->sizeX, $this->sizeY);
		$this->label->setPosition(2, -$this->sizeY / 2);
		$this->label->setSize($this->sizeX - 2, $this->sizeY - 1);
	}
}

?>