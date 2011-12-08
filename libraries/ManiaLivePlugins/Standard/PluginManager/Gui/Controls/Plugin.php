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

namespace ManiaLivePlugins\Standard\PluginManager\Gui\Controls;

use ManiaLib\Gui\Elements\BgsPlayerCard;
use ManiaLib\Gui\Elements\Icons64x64_1;
use ManiaLib\Gui\Elements\Label;
use ManiaLive\PluginHandler\PluginHandler;

class Plugin extends \ManiaLive\Gui\Control
{
	private $background;
	private $pluginName;
	private $loadState;
	private $loadButton;
	
	private $loadAction;
	private $unloadAction;
	
	function __construct($pluginName, $pluginClass, $manager)
	{
		$this->sizeY = 6;
		
		$this->background = new BgsPlayerCard();
		$this->background->setSubStyle(BgsPlayerCard::BgCardSystem);
		$this->addComponent($this->background);
		
		$this->pluginName = new Label();
		$this->pluginName->setText('$fff'.$pluginName.'$z');
		$this->addComponent($this->pluginName);
		
		$this->loadState = new Label();
		$this->addComponent($this->loadState);
		
		$this->loadButton = new Icons64x64_1(7);
		$this->addComponent($this->loadButton);
		
		$this->loadAction = $this->createAction(array($manager, 'loadPlugin'), $pluginClass);
		$this->unloadAction = $this->createAction(array($manager, 'unloadPlugin'), $pluginClass);
		$this->setIsLoaded(PluginHandler::getInstance()->isPluginLoaded($pluginName));
	}
	
	function setIsLoaded($isLoaded)
	{
		if($isLoaded)
		{
			$this->loadState->setText('$0f0Loaded');
			$this->loadButton->setSubStyle(Icons64x64_1::ClipPause);
			$this->loadButton->setAction($this->unloadAction);
		}
		else
		{
			$this->loadState->setText('$f00Unloaded');
			$this->loadButton->setSubStyle(Icons64x64_1::ClipPlay);
			$this->loadButton->setAction($this->loadAction);
		}
		$this->redraw();
	}
	
	protected function onResize($oldX, $oldY)
	{	
		$this->background->setSize($this->sizeX, $this->sizeY);
		
		$this->pluginName->setSizeX(($this->sizeX - 5) * 0.7);
		$this->pluginName->setPosition(1, -$this->sizeY / 2);
		$this->pluginName->setValign('center2');
		
		$this->loadState->setSizeX(($this->sizeX - 5) * 0.3);
		$this->loadState->setValign('center2');
		$this->loadState->setPosition($this->pluginName->getBorderRight() + 1, -$this->sizeY / 2);
		
		$this->loadButton->setValign('center');
		$this->loadButton->setPosition($this->loadState->getBorderRight() - 4.5, -$this->sizeY / 2);
	}
}

?>