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

namespace ManiaLive\Gui\Windowing\Windows;

use ManiaLib\Gui\Layouts\Line;
use ManiaLib\Gui\Elements\Bgs1;
use ManiaLib\Gui\Elements\Label;
use ManiaLive\Gui\Windowing\Controls\Frame;
use ManiaLive\Gui\Windowing\Controls\ButtonResizeable;
use ManiaLive\Gui\Windowing\Controls\Panel;

/**
 * @author Florian Schnell
 */
class Dialog extends \ManiaLive\Gui\Windowing\Window
{
	public $labels;
	
	protected $bg;
	protected $panel;
	protected $text;
	protected $answer;
	protected $container;
	protected $buttons;
	
	const CANCEL = 1;
	const OK = 2;
	const APPLY = 4;
	const RETRY = 8;
	const NO = 16;
	const YES = 32;
	
	function initializeComponents()
	{
		$this->labels = array
		(
			self::CANCEL => 'Cancel',
			self::OK => 'OK',
			self::APPLY => 'Apply',
			self::RETRY => 'Retry',
			self::NO => 'No',
			self::YES => 'Yes'
		); 
		
		$this->bg = new \ManiaLib\Gui\Elements\Quad(325, 184);
		$this->bg->setBgColor('234C');
		$this->addComponent($this->bg);
			
		$this->panel = new Panel();
		$this->panel->setBackgroundStyle(Bgs1::BgWindow2);
		$this->addComponent($this->panel);
		
		$this->text = new Label();
		$this->text->enableAutonewline();
		$this->addComponent($this->text);
		
		$this->container = new Frame();
		$this->container->applyLayout(new Line());
		$this->addComponent($this->container);
	}
	
	function onShow()
	{
		$this->panel->setSize($this->getSizeX(), $this->getSizeY());
		
		$this->bg->setAlign('center','center');
		$this->bg->setPosition($this->getSizeX() / 2, $this->getSizeY() / 2 + 5);
		
		// create container for buttons ...
		$this->container->setSizeX($this->getSizeX());
		$this->container->setHalign('center');
		$this->container->setPosition($this->getSizeX() / 2, $this->getSizeY() - 8);
		$this->container->clearComponents();
		
		// position and resize text ...
		$this->text->setPosition(2, 17);
		$this->text->setSize($this->sizeX - 4, $this->sizeY - 6);
		
		// count buttons ...
		$i = 32;
		$code = $this->buttons;
		$buttons = array();
		$buttons_count = 0;
		while ($i > 0)
		{
			if ($code >= $i)
			{
				$code -= $i;
				$buttons[] = $i;
				$buttons_count++;
			}
			$i = floor($i/2);
		}
		
		// add buttons to the window ...
		foreach ($buttons as $button)
		{
			$ui = new ButtonResizeable($this->getSizeX() / $buttons_count - 2.5 / $buttons_count, 7);
			$ui->setPositionX(1.25);
			$ui->setText($this->labels[$button]);
			$ui->setAction($this->callback('onButton', $button));
			$this->container->addComponent($ui);
		}
	}
	
	function setTitle($title)
	{
		$this->panel->setTitle($title);
	}
	
	function setText($text)
	{
		$this->text->setText($text);
	}
	
	function onButton($login, $button)
	{
		$this->answer = $button;
		$this->hide();
	}
	
	function getAnswer()
	{
		return $this->answer;
	}
	
	function setButtons($buttons)
	{
		$this->buttons = $buttons;
	}
}

?>