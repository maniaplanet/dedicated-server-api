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
namespace ManiaLivePlugins\Standard\Menubar\Gui\Windows;

use ManiaLive\Gui\Windowing\Control;

use ManiaLive\Gui\Windowing\Controls\Frame;

use ManiaLib\Gui\Layouts\Column;
use ManiaLib\Gui\Elements\BgsPlayerCard;
use ManiaLib\Gui\Elements\Label;
use ManiaLive\Gui\Windowing\Window;
use ManiaLivePlugins\Standard\Menubar\Gui\Controls\Item;

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

		foreach ($components as $component)
			$this->container->addComponent($component);
	}
}

?>