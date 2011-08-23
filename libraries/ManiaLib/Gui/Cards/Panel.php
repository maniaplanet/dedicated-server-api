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
	
	function __construct ($sx=187.5, $sy=200)
	{	
		$this->sizeX = $sx;
		$this->sizeY = $sy;

		$this->cardElementsHalign = 'center';
		$this->cardElementsPosY = -1.5;
		
		$titleBgWidth = $sx;
		$titleWidth = $sx - 6;
		
		$this->setStyle(Quad::Bgs1);
		$this->setSubStyle(Bgs1::BgWindow3);
		
		$this->titleBg = new Quad ($titleBgWidth, 16.25	);
		$this->titleBg->setAlign("center", 'center');
		$this->titleBg->setStyle(Quad::Bgs1);
		$this->titleBg->setSubStyle(Bgs1::BgTitle3_1);
		$this->titleBg->setPosY(10);
		
		$this->addCardElement($this->titleBg);
		
		$this->title = new Label($titleWidth);
		$this->title->setAlign('center', 'center2');
		$this->title->setPositionY(10);
		$this->title->setStyle(Label::TextTitle3);
		
		$this->addCardElement($this->title);
	}
	
	function setSizeX($x)
	{
		parent::setSizeX($x);
		$this->titleBg->setSizeX($x);
		$this->title->setSizeX($x-4);
	}
}

?>