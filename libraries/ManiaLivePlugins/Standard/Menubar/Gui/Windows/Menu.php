<?php

namespace ManiaLivePlugins\Standard\Menubar\Gui\Windows;

use ManiaLive\Gui\Windowing\Controls\Frame;
use ManiaLib\Gui\Layouts\Column;
use ManiaLive\Gui\Windowing\Window;

class Menu extends Window
{

	private $container;

	function initializeComponents()
	{
		$this->container = new Frame(0, 0, new Column(0, 0, Column::DIRECTION_UP));
		$this->addComponent($this->container);
	}

	function set($components)
	{
		$this->container->clearComponents();

		foreach($components as $component)
			$this->container->addComponent($component);
	}

}

?>