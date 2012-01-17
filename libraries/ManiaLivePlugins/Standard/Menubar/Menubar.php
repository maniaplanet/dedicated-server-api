<?php
/**
 * Menubar Plugin - Handle dynamically a menu
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\Standard\Menubar;

use ManiaLib\Gui\Elements\Icons128x128_1;
use ManiaLive\Features\Admin\AdminGroup;
use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;
use ManiaLive\PluginHandler\Event as PluginEvent;
use ManiaLivePlugins\Standard\Menubar\Gui\Controls\Subitem;
use ManiaLivePlugins\Standard\Menubar\Gui\Controls\Item;
use ManiaLivePlugins\Standard\Menubar\Gui\Windows\Menu;

class Menubar extends \ManiaLive\PluginHandler\Plugin
{
	protected $menu;
	protected $loaded = false;
	
	public function onInit()
	{
		$this->menu = array();
		$this->setVersion('1.1');
		$this->setPublicMethod('addButton');
		$this->setPublicMethod('initMenu');
	}
	
	public function onReady()
	{
		$this->refreshMenubar();
		
		$this->enableDedicatedEvents(ServerEvent::ON_PLAYER_CONNECT);
		$this->enablePluginEvents(PluginEvent::ON_PLUGIN_UNLOADED);
		
		$this->loaded = true;
	}
	
	public function refreshMenubar()
	{
		foreach($this->storage->players as $login => $player)
			$this->onPlayerConnect($login, true);
		foreach($this->storage->spectators as $login => $player)
			$this->onPlayerConnect($login, false);
	}
	
	public function onPlayerConnect($login, $isSpectator)
	{
		$menu = Menu::Create($login);
		$menu->setPosition(136, 45);
		$menu->setScale(0.8);
		$menu->clearItems();
		
		foreach($this->menu as $section)
		{
			switch(count($section['buttons']))
			{
				case 0: break;
				case 1:
					if(!$section['buttons'][0]['admin'] || AdminGroup::contains($login))
						$menu->addFinalItem($section['name'], $section['icon'], $section['buttons'][0]['callback']);
					break;
				default:
					$entry = $menu->addItem($section['name'], $section['icon']);
					foreach($section['buttons'] as $button)
						if(!$button['admin'] || AdminGroup::contains($login))
							$entry->addSubitem($button['name'], $button['callback']);
			}
		}
		
		$menu->show();
	}
	
	// initializes the menu for a specific plugin
	function initMenu($icon, $pluginId = null)
	{
		$id = explode('\\', $pluginId);
		
		$this->menu[$pluginId] = array('name' => end($id), 'icon' => $icon, 'buttons' => array());
		
		if($this->loaded)
			$this->refreshMenubar();
	}
	
	// adds button for the plugin.
	function addButton($name, $callback, $admin = false, $pluginId = null)
	{
		if(!isset($this->menu[$pluginId]))
			$this->initMenu(Icons128x128_1::DefaultIcon, $pluginId);
		
		$this->menu[$pluginId]['buttons'][] = array('name' => $name, 'callback' => $callback, 'admin' => $admin);
		
		if($this->loaded)
			$this->refreshMenubar();
	}
	
	function onPluginUnloaded($pluginId)
	{
		// ignore own unloading
		if ($pluginId == $this->getId())
			return;
		
		// remove button from bar
		if(isset($this->menu[$pluginId]))
		{
			unset($this->menu[$pluginId]);
			$this->refreshMenubar();
		}
	}
	
	function onUnload()
	{
		Menu::EraseAll();
		
		parent::onUnload();
	}
}

?>