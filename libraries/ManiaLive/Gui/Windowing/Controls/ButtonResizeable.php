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

namespace ManiaLive\Gui\Windowing\Controls;

use ManiaLive\Gui\Toolkit\Elements\Bgs1InRace;
use ManiaLive\Gui\Toolkit\Elements\Label;
use ManiaLive\Gui\Toolkit\Elements\Button;

/**
 * Use this button if you need something
 * more dynamic, you can't change size for
 * standard buttons.
 * 
 * @author Florian Schnell
 */
class ButtonResizeable extends \ManiaLive\Gui\Windowing\Control
{
	protected $button;
	protected $label;
	
	function initializeComponents()
	{
		$this->sizeX = $this->getParam(0);
		$this->sizeY = $this->getParam(1);
		
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