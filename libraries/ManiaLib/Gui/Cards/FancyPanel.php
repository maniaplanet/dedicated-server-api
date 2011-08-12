<?php
/**
 * ManiaLib - Lightweight PHP framework for Manialinks
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLib\Gui\Cards;

use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\Icons128x128_1;
use ManiaLib\Gui\Elements\Bgs1;

class FancyPanel extends Bgs1
{
	/**
	 * @var \ManiaLib\Gui\Elements\Label
	 */
	public $title;
	/**
	 * @var \ManiaLib\Gui\Elements\Label
	 */
	public $subtitle;
	/**
	 * @var \ManiaLib\Gui\Elements\Icons128x128_1
	 */
	public $icon;
		
	function __construct ($sizeX=70, $sizeY=60)
	{	
		parent::__construct($sizeX, $sizeY);
		
		$this->cardElementsPosX = 2;
		$this->cardElementsPosY = -3;
		
		$this->icon = new Icons128x128_1(8);
		$this->addCardElement($this->icon);
		
		$this->title = new Label($sizeX - 10, 5);
		$this->title->setStyle(Label::TextRankingsBig);
		$this->addCardElement($this->title);
		
		$this->subtitle = new Label($sizeX - 10, 3);
		$this->subtitle->setStyle(Label::TextInfoSmall);
		$this->addCardElement($this->subtitle);
	}
	
	protected function preFilter()
	{
		$this->title->incPosX($this->icon->getSizeX()+1);
		$this->title->incPosY(-0.5);
		$this->subtitle->incPosX($this->icon->getSizeX()+1);
		$this->subtitle->incPosY(-4);
	}
	
}


?>