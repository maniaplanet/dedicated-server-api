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

/**
 * Use this button if you need something
 * more dynamic, you can't change size for
 * standard buttons.
 */
class ButtonResizable extends \ManiaLive\Gui\Control
{
	protected $button;
	protected $label;
	
	function __construct($sizeX=20, $sizeY=4)
	{
		$this->sizeX = $sizeX;
		$this->sizeY = $sizeY;
		
		$this->button = new Bgs1InRace();
		$this->button->setSubStyle(Bgs1InRace::BgButton);
		$this->addComponent($this->button);
		
		$this->label = new Label();
		$this->label->setAlign('center', 'center2');
		$this->label->setStyle(Label::TextButtonSmall);
		//$this->label->setTextColor('08f');
		$this->addComponent($this->label);
	}
	
	function onDraw()
	{
		$this->button->setSize($this->sizeX, $this->sizeY);
		
		$this->label->setTextSize($this->sizeY - 2);
		$this->label->setSize($this->sizeX - 3, $this->sizeY - 1);
		$this->label->setPosition($this->sizeX / 2, -$this->sizeY / 2);
	}
	
	function getText()
	{
		return $this->label->getText();
	}
	
	function setText($text)
	{
		$this->label->setText('$08f'.$text);
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