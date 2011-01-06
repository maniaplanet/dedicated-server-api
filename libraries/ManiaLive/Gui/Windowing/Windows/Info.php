<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\Gui\Windowing\Windows;

use ManiaLive\Gui\Toolkit\Elements\Icons64x64_1;
use ManiaLive\Gui\Toolkit\Elements\Label;
use ManiaLive\Gui\Windowing\Controls\Panel;

/**
 * @author Florian Schnell
 */
class Info extends \ManiaLive\Gui\Windowing\Window
{
	protected $title;
	protected $text;
	protected $panel;
	protected $button;
	
	protected function initializeComponents()
	{
		$this->panel = new Panel();
		$this->addComponent($this->panel);

		$this->text = new Label();
		$this->text->enableAutonewline();
		$this->addComponent($this->text);
		
		$this->button = new Icons64x64_1(3);
		$this->button->setSubStyle(Icons64x64_1::Close);
		$this->button->setAction($this->callback('hide'));
		$this->addComponent($this->button);
	}
	
	protected function onHide() {}
	
	protected function onShow()
	{
		$this->button->setPosition($this->sizeX - 5, 1.6);
		
		// stretch panel to fill window size ...
		$this->panel->setSize($this->sizeX, $this->sizeY);
		
		// position and resize text ...
		$this->text->setPosition(2, 6);
		$this->text->setSize($this->sizeX - 4, $this->sizeY - 6);
	}
	
	function setText($text)
	{		
		$this->text->setText($text);
	}
	
	function setTitle($title)
	{
		$this->panel->setTitle($title);
	}
}

?>