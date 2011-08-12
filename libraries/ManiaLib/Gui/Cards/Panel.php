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

use ManiaLib\Gui\Elements\Bgs1;

use ManiaLib\Gui\Elements\Quad;

/**
 * Panel
 * Very useful! A quad with a title and a title background
 */
class Panel extends Quad
{
	/**
	 * @var \ManiaLib\Gui\Elements\Label
	 */
	public $title;
	/**
	 * Title background
	 * @var \ManiaLib\Gui\Elements\Quad
	 */
	public $titleBg;
	
	function __construct ($sx=100, $sy=80)
	{	
		$this->sizeX = $sx;
		$this->sizeY = $sy;

		$this->cardElementsHalign = 'center';
		$this->cardElementsPosY = -1.5;
		
		$titleBgWidth = $sx - 4;
		$titleWidth = $sx - 6;
		
		$this->setStyle(Quad::Bgs1);
		$this->setSubStyle(Bgs1::BgWindow3);
		
		$this->titleBg = new Quad ($titleBgWidth, 6.5);
		$this->titleBg->setHalign("center");
		$this->titleBg->setStyle(Quad::Bgs1);
		$this->titleBg->setSubStyle(Bgs1::BgTitle3);
		
		$this->addCardElement($this->titleBg);
		
		$this->title = new Label($titleWidth);
		$this->title->setAlign('center', 'center');
		$this->title->setPositionY(-3.25);
		$this->title->setStyle(Label::TextTitle3);
		
		$this->addCardElement($this->title);
	}
	
	function setSizeX($x)
	{
		parent::setSizeX($x);
		$this->titleBg->setSizeX($x-2);
		$this->title->setSizeX($x-4);
	}
}

?>