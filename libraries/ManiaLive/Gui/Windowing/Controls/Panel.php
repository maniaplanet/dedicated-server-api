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

use ManiaLive\Gui\Toolkit\Elements\Icons64x64_1;

use ManiaLive\Gui\Toolkit\Tools;
use ManiaLive\Gui\Toolkit\Elements\Label;
use ManiaLive\Gui\Toolkit\Elements\Quad;
use ManiaLive\Gui\Toolkit\DefaultStyles;
use ManiaLive\Gui\Windowing\Control;

/**
 * Use this to quickly build your own windows.
 * 
 * @author Florian Schnell
 */
class Panel extends Control
{
	public $title;
	public $titleBg;
	public $main;
	public $header;
	public $btn_close;
	
	function initializeComponents()
	{
		$this->sizeX = $this->getParam(0);
		$this->sizeY = $this->getParam(1);
		
		// form background ...
		$this->main = new Quad();
		$this->main->setStyle(DefaultStyles::Panel_Style);
		$this->main->setSubStyle(DefaultStyles::Panel_Substyle);
		$this->addComponent($this->main);
		
		// title background ...
		$this->titleBg = new Quad();
		$this->titleBg->setStyle(DefaultStyles::Panel_TitleBg_Style);
		$this->titleBg->setSubStyle(DefaultStyles::Panel_TitleBg_Substyle);
		$this->titleBg->setSizeY(4);
		
		// title label ...
		$this->title = new Label();
		$this->title->setStyle(Label::TextTitle1);
		$this->title->setTextColor('fff');
		$this->title->setTextSize(3);
		$this->title->setPositionY(0.75);
		
		// move title label and background together ...
		$this->header = new Frame();
		$this->header->addComponent($this->titleBg);
		$this->header->addComponent($this->title);
		$this->addComponent($this->header);
		
		// create close button ...
		$this->btn_close = new Icons64x64_1(3);
		$this->btn_close->setSubStyle(Icons64x64_1::Close);
		$this->addComponent($this->btn_close);
	}
	
	function beforeDraw()
	{
		// set action for close button ...
		$this->btn_close->setPosition($this->getSizeX() - 5, 1.6);
		$this->btn_close->setAction($this->callback(array($this->getWindow(), 'hide')));
		
		// set size of form ...
		$this->main->setSize($this->sizeX, $this->sizeY);
		
		// set width of title according to control size ...
		$this->titleBg->setSizeX($this->getSizeX() - 2);
		$this->title->setSizeX($this->getSizeX() - 4);

		// align center ...
		$this->titleBg->setHalign('center');
		$this->title->setHalign('center');
		
		// move header to right position ..
		$this->header->setPosition($this->sizeX / 2, 1);
	}
	
	function setTitle($title)
	{
		$this->title->setText($title);
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