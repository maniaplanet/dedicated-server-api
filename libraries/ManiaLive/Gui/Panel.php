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

namespace ManiaLive\Gui;

use ManiaLib\Gui\Elements\Bgs1InRace;
use ManiaLib\Gui\Elements\Icons64x64_1;
use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\Quad;
use ManiaLive\Gui\Controls\Frame;

/**
 * Use this to quickly build your own windows.
 * 
 * @author Florian Schnell
 */
abstract class Panel extends Window
{
	private $title;
	protected $titleBg;
	protected $main;
	private $header;
	private $buttonClose;
	
	protected function onConstruct($sizeX=70, $sizeY=60)
	{
		// form background ...
		$this->main = new Bgs1InRace();
		$this->main->setSubStyle(Bgs1InRace::BgWindow2);
		$this->main->setPosY(-14);
		$this->addComponent($this->main);
		
		// title background ...
		$this->titleBg = new Bgs1InRace();
		$this->titleBg->setSubStyle(Bgs1InRace::BgTitle3_1);
		$this->titleBg->setPosX(-1);
		$this->titleBg->setSizeY(14);
		
		// title label ...
		$this->title = new Label();
		$this->title->setStyle(Label::TextCardScores2);
		$this->title->setTextColor('fff');
		$this->title->setTextSize(2.5);
		$this->title->setPosY(-7);
		$this->title->setAlign('center', 'center2');
		
		// move title label and background together ...
		$this->header = new Frame();
		$this->header->addComponent($this->titleBg);
		$this->header->addComponent($this->title);
		$this->addComponent($this->header);
		
		// create close button ...
		$this->buttonClose = new Icons64x64_1(8);
		$this->buttonClose->setSubStyle(Icons64x64_1::Close);
		$this->buttonClose->setPosition(1.5, -7);
		$this->buttonClose->setValign('center');
		$this->buttonClose->setAction($this->createAction(array($this, 'hide')));
		$this->addComponent($this->buttonClose);
		
		$this->setSize($sizeX, $sizeY);
	}
	
	function onResize($oldX, $oldY)
	{
		// set size of form ...
		$this->main->setSize($this->sizeX, $this->sizeY - 14);
		
		// set width of title according to control size ...
		$this->titleBg->setSizeX($this->sizeX + 2);
		$this->title->setSizeX($this->sizeX - 4);
		$this->title->setPosX($this->sizeX / 2);
	}
	
	function setTitle($title)
	{
		$this->title->setText('$o'.$title);
	}
	
	function getTitle()
	{
		$this->title->getText();
	}
	
	function showCloseButton($bool)
	{
		$this->buttonClose->setVisibility($bool);
	}
	
	function setBackgroundStyle($substyle)
	{
		$this->main->setSubStyle($substyle);
	}
	
	function makeTransparent()
	{
		$this->setBackgroundStyle(Bgs1InRace::BgWindow1);
	}
	
	function makeOpaque()
	{
		$this->setBackgroundStyle(Bgs1InRace::BgTitle2);
	}
}

?>