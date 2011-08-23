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

use ManiaLive\PluginHandler\RepositoryEntry;
use ManiaLivePlugins\Standard\PluginManager\Gui\Windows\Update;
use ManiaLive\Gui\Windowing\Windows\Info;
use ManiaLive\PluginHandler\PluginHandler;
use ManiaLib\Gui\Elements\BgsPlayerCard;
use ManiaLib\Gui\Elements\Icons64x64_1;
use ManiaLib\Gui\Elements\Icons128x128_1;
use ManiaLib\Gui\Elements\Quad;
use ManiaLib\Gui\Elements\Label;

class Plugin extends \ManiaLive\Gui\Windowing\Control
{
	public $loadCallBack;
	public $unloadCallBack; 
	
	protected $labelName;
	protected $labelLoaded;
	protected $buttonLoad;
	protected $background;
	
	protected $name;
	protected $loaded;
	protected $class;
	
	function initializeComponents()
	{
		$this->name = $this->getParam(0);
		$this->loaded = $this->getParam(1);
		$this->class = $this->getParam(2);
		
		$this->sizeY = 3.5;
		
		$this->background = new Quad();
		$this->background->setStyle(BgsPlayerCard::BgsPlayerCard);
		$this->background->setSubStyle(BgsPlayerCard::BgCardSystem);
		$this->addComponent($this->background);
		
		$this->labelName = new Label();
		$this->labelName->setText($this->name);
		$this->addComponent($this->labelName);
		
		$this->labelLoaded = new Label();
		$this->addComponent($this->labelLoaded);
		
		$this->buttonLoad = new Quad(4.5, 4.5);
		$this->buttonLoad->setStyle(Quad::Icons64x64_1);
		$this->addComponent($this->buttonLoad);
		
	}
	
	function beforeDraw()
	{
		if ($this->loaded)
		{
			$this->labelLoaded->setText('$0f0Loaded');
			$this->buttonLoad->setSubStyle(Icons64x64_1::ClipPause);
			$this->buttonLoad->setAction($this->callback('onUnload'));
			$this->buttonLoad->setVisibility(true);
		}
		else
		{
			$this->labelLoaded->setText('$f00Unloaded');
			$this->buttonLoad->setSubStyle(Icons64x64_1::ClipPlay);
			$this->buttonLoad->setAction($this->callback('onLoad'));
			$this->buttonLoad->setVisibility(true);
		}
		$this->buttonLoad->setVisibility($this->name != 'Standard\PluginManager');
	}
	
	function onResize()
	{
		$this->labelName->setPosition(1, 0.7);
		$this->labelName->setSizeX(($this->sizeX - 5) * 0.7);
		
		$this->labelLoaded->setSizeX(($this->sizeX - 5) * 0.3);
		$this->labelLoaded->setPosition($this->labelName->getBorderRight() + 1, 0.7);
		
		$this->buttonLoad->setPosition($this->labelLoaded->getBorderRight() - 1.8, -0.5);
		
		$this->background->setSize($this->sizeX, $this->sizeY);
	}
	
	function afterDraw() {}
	
	function onLoad($login)
	{
		call_user_func($this->loadCallBack, $login, $this->class);
	}
	
	function onUnload($login)
	{
		call_user_func($this->unloadCallBack, $login, $this->class);
	}
	
	function destroy()
	{
		$this->loadCallBack = null;
		$this->unloadCallBack = null;
		parent::destroy();
	}
}

?>