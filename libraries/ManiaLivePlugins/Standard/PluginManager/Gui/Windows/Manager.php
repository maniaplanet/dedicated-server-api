<?php
/**
 * Plugin Manager - Plugin mades to load or unload ManiaLive plugins
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\Standard\PluginManager\Gui\Windows;

use ManiaLive\Gui\Controls\Pager;
use ManiaLive\PluginHandler\PluginHandler;
use ManiaLivePlugins\Standard\PluginManager\Gui\Controls\Plugin;

class Manager extends \ManiaLive\Gui\ManagedWindow
{
	static private $plugins = array();
	
	private $pager;
	
	static function AddPlugin($pluginClass, $manager)
	{
		$pluginId = PluginHandler::getPluginIdFromClass($pluginClass);
		if($pluginId == 'Standard\PluginManager' || isset(self::$plugins[$pluginId]))
			return;
		
		$plugin = new Plugin($pluginId, $pluginClass, $manager);
		self::$plugins[$pluginId] = $plugin;
		
		foreach(self::GetAll() as $window)
			$window->pager->addItem($plugin);
	}
	
	static function GetPlugin($pluginId)
	{
		if(isset(self::$plugins[$pluginId]))
			return self::$plugins[$pluginId];
		return null;
	}
	
	static function ErasePlugins()
	{
		foreach(self::$plugins as $plugin)
			$plugin->destroy();
	}
	
	protected function onConstruct()
	{
		parent::onConstruct();
		$this->setTitle('Plugin Manager');
		$this->setMaximizable();
		
		$this->pager = new Pager();
		$this->pager->setPosition(2, -16);
		$this->pager->setStretchContentX(true);
		$this->addComponent($this->pager);
		
		foreach(self::$plugins as $plugin)
			$this->pager->addItem($plugin);
	}
	
	function onResize($oldX, $oldY)
	{
		parent::onResize($oldX, $oldY);
		$this->pager->setSize($this->sizeX - 4, $this->sizeY - 20);
	}
}
?>