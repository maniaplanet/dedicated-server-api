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

use ManiaLib\Gui\Elements\Bgs1;
use ManiaLib\Gui\Elements\Label;

class Subitem extends \ManiaLive\Gui\Control
{
	private $background;
	
	function __construct($name = '', $sizeX = 30, $sizeY = 6)
	{
		$this->sizeX = $sizeX;
		$this->sizeY = $sizeY;
		
		$this->background = new Bgs1($sizeX, $sizeY);
		$this->background->setSubStyle(Bgs1::NavButtonBlink);
		$this->addComponent($this->background);
		
		$label = new Label();
		$label->setStyle(Label::TextCardSmallScores2Rank);
		$label->setAlign('left', 'center2');
		$label->setPosition(1, -$sizeY / 2);
		$label->setText('$i'.$name);
		$this->addComponent($label);
	}
	
	function setAction($action)
	{
		$this->background->setAction($action);
	}
}