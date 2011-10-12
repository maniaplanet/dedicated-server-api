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

namespace ManiaLivePlugins\Standard\Profiler\Gui\Controls;

use ManiaLib\Gui\Elements\Button;
use ManiaLive\Gui\Windowing\Controls\Pager;
use ManiaLive\PluginHandler\PluginHandler;
use ManiaLivePlugins\Standard\Profiler\Profiler;
use ManiaLive\Gui\Handler\GuiHandler;
use ManiaLive\DedicatedApi\Xmlrpc\Client_Gbx;
use ManiaLive\Database\Connection;
use ManiaLive\Threading\Commands\Command;
use ManiaLive\Threading\ThreadPool;
use ManiaLib\Gui\Elements\Label;

class PluginsTab extends \ManiaLive\Gui\Windowing\Controls\Tab
{
	protected $btnPluginManger;
	protected $pgPlugins;
	protected $pluginManagerAvailable;
	
	function initializeComponents()
	{
		$this->pluginManagerAvailable = PluginHandler::getInstance()->isPluginLoaded('Standard\PluginManager');
		
		$this->btnPluginManger = new Button();
		$this->btnPluginManger->setHalign('center');
		$this->btnPluginManger->setStyle(Button::CardButtonSmallWide);
		$this->btnPluginManger->setText('click here to manage your plugins');
		$this->btnPluginManger->setVisibility($this->pluginManagerAvailable);
		$this->addComponent($this->btnPluginManger);
		
		$this->pgPlugins = new Pager();
		$this->pgPlugins->setPosition(0, $this->pluginManagerAvailable ? 8 : 2);
		$this->pgPlugins->setStretchContentX(true);
		$this->addComponent($this->pgPlugins);
	}
	
	function showPluginManager($login)
	{
		PluginHandler::getInstance()->callPublicMethod(Profiler::$me, 'Standard\PluginManager', 'openWindow', array($login));
	}
	
	function refreshList()
	{
		$this->pgPlugins->clearItems();
		foreach (PluginHandler::getInstance()->getLoadedPluginsList() as $plugin)
		{
			$plugin = explode('\\', $plugin);
			$author = array_shift($plugin);
			$package = implode('\\', $plugin);
			
			$item = new ListItem();
			$item->label->setText('Author: ' . $author . ' - Package: ' . $package);
			$this->pgPlugins->addItem($item);
		}
	}
	
	function onResize()
	{
		$this->btnPluginManger->setPosition($this->sizeX / 2, 1);
		$this->pgPlugins->setSize($this->sizeX, $this->sizeY - ($this->pluginManagerAvailable ? 12 : 2));
	}
	
	function beforeDraw()
	{
		$this->btnPluginManger->setAction($this->callback('showPluginManager'));
	}
}

?>