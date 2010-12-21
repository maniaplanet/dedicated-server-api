<?php

namespace ManiaLive\Gui\Windowing\Windows;

use ManiaLive\Gui\Toolkit\Layouts\Line;
use ManiaLive\Gui\Windowing\Controls\Frame;
use ManiaLive\Gui\Toolkit\Elements\Bgs1;
use ManiaLive\Gui\Windowing\Controls\ButtonResizeable;
use ManiaLive\Gui\Windowing\Controls\Panel;

use ManiaLive\Gui\Toolkit\Elements\Label;

class Dialog extends \ManiaLive\Gui\Windowing\Window
{
	public $labels;
	
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
		
		$this->panel = new Panel();
		$this->panel->setBackgroundStyle(Bgs1::BgWindow1);
		$this->addComponent($this->panel);
		
		$this->text = new Label();
		$this->addComponent($this->text);
		
		$this->container = new Frame();
		$this->container->applyLayout(new Line());
		$this->addComponent($this->container);
	}
	
	function onShow()
	{
		$this->panel->setSize($this->getSizeX(), $this->getSizeY());
		
		// create container for buttons ...
		$this->container->setSizeX($this->getSizeX());
		$this->container->setHalign('center');
		$this->container->setPosition($this->getSizeX() / 2, $this->getSizeY() - 5);
		$this->container->clearComponents();
		
		// position and resize text ...
		$this->text->setPosition(2, 6);
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
			$ui = new ButtonResizeable($this->getSizeX() / $buttons_count - 1 / $buttons_count, 4);
			$ui->setPositionX(0.5);
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