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

use ManiaLib\Gui\Elements\Bgs1InRace;
use ManiaLib\Gui\Elements\BgsPlayerCard;
use ManiaLive\Gui\Windowing\WindowHandler;
use ManiaLib\Gui\Elements\Icons64x64_1;
use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\Quad;
use ManiaLib\Gui\DefaultStyles;

/**
 * Use this to quickly build your own windows.
 * 
 * @author Florian Schnell
 */
class Panel extends \ManiaLive\Gui\Windowing\Control
{
	public $title;
	public $titleBg;
	public $main;
	public $header;
	public $btn_close;
	
	function initializeComponents()
	{
		// form background ...
		$this->main = new Quad();
		$this->main->setStyle(DefaultStyles::Panel_Style);
		$this->main->setSubStyle(DefaultStyles::Panel_Substyle);
		$this->addComponent($this->main);
		
		// title background ...
		$this->titleBg = new Quad();
		$this->titleBg->setStyle(Quad::Bgs1InRace);
		$this->titleBg->setSubStyle(DefaultStyles::Panel_TitleBg_Substyle);
		$this->titleBg->setSizeY(14);
		$this->titleBg->setHalign('center');
		
		// title label ...
		$this->title = new Label();
		$this->title->setStyle(Label::TextCardScores2);
		$this->title->setTextColor('fff');
		$this->title->setTextSize(2.5);
		$this->title->setPositionY(7);
		$this->title->setAlign('center', 'center2');
		
		// move title label and background together ...
		$this->header = new Frame();
		$this->header->addComponent($this->titleBg);
		$this->header->addComponent($this->title);
		$this->addComponent($this->header);
		
		// create close button ...
		$this->btn_close = new Icons64x64_1(8);
		$this->btn_close->setSubStyle(Icons64x64_1::Close);
		$this->addComponent($this->btn_close);
		
		$this->setSize($this->getParam(0), $this->getParam(1));
	}
	
	function onResize()
	{
		// set action for close button ...
		$this->btn_close->setPosition(2.5, 7);
		$this->btn_close->setValign('center');
		
		// set size of form ...
		$this->main->setSize($this->sizeX, $this->sizeY - 14);
		$this->main->setPosY(14);
		
		// set width of title according to control size ...
		$this->titleBg->setSizeX($this->getSizeX() + 2);
		$this->title->setSizeX($this->getSizeX() + 4);
		
		$this->header->setPosition($this->sizeX / 2, 0);
	}
	
	function beforeDraw()
	{
		$this->btn_close->setAction($this->callback(array($this->getWindow(), 'hide')));
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
		$this->btn_close->setVisibility($bool);
	}
	
	function setBackgroundStyle($substyle)
	{
		$this->main->setSubStyle($substyle);
	}
}

?>