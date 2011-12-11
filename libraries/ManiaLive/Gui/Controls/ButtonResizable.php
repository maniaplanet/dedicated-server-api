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

namespace ManiaLive\Gui\Controls;

use ManiaLib\Gui\Elements\Bgs1InRace;
use ManiaLib\Gui\Elements\Label;
use ManiaLive\Gui\ActionHandler;

/**
 * Use this button if you need something
 * more dynamic, you can't change size for
 * standard buttons.
 */
class ButtonResizable extends \ManiaLive\Gui\Control
{
	protected $label;
	
	function __construct($sizeX=35, $sizeY=7)
	{
		$this->label = new Label();
		$this->label->setAlign('center', 'center2');
		$this->label->setStyle(Label::TextButtonNav);
		$this->label->setFocusAreaColor1('000');
		$this->label->setFocusAreaColor2('fff');
		$this->addComponent($this->label);
		
		$this->setSize($sizeX, $sizeY);
	}
	
	protected function onResize($oldX, $oldY)
	{
		$this->label->setScale($this->sizeY / 6.5);
		$this->label->setSize(6.5 * $this->sizeX / $this->sizeY - .25, 6.5);
		$this->label->setPosition($this->sizeX / 2, -$this->sizeY / 2);
	}
	
	function onDraw()
	{
		if($this->label->getAction() == null && $this->label->getManialink() == null && $this->label->getUrl() == null)
			$this->label->setAction(ActionHandler::NONE);
	}
	
	function getText()
	{
		return $this->label->getText();
	}
	
	function setText($text)
	{
		$this->label->setText('$fff'.$text);
	}
	
	function setAction($action)
	{
		$this->label->setAction($action);
	}
	
	function setUrl($url)
	{
		$this->label->setUrl($url);
	}
	
	function setManialink($manialink)
	{
		$this->label->setManialink($manialink);
	}
}

?>