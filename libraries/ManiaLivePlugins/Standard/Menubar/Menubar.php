<?php

namespace ManiaLivePlugins\Standard\Menubar;

use ManiaLivePlugins\Standard\Version;
use ManiaLib\Gui\Elements\Icons128x128_1;
use ManiaLivePlugins\Standard\Menubar\Gui\Controls\Subitem;
use ManiaLive\Gui\Windowing\CustomUI;
use ManiaLive\Gui\Windowing\WindowHandler;
use ManiaLive\Features\Admin\AdminGroup;
use ManiaLive\Config\Loader;
use ManiaLive\Gui\Windowing\Window;
use ManiaLivePlugins\Standard\Menubar\Gui\Controls\Item;
use ManiaLivePlugins\Standard\Menubar\Gui\Windows\Menu;
use ManiaLive\PluginHandler\Plugin;
use ManiaLivePlugins\Standard\TestPlugin;

class Menubar extends \ManiaLive\PluginHandler\Plugin
{
	static $menu;
	protected $loaded = false;
	
	public function onInit()
	{
		self::$menu = array();
		$this->setVersion(1.1);
		$this->setPublicMethod('addButton');
		$this->setPublicMethod('initMenu');
	}
	
	protected function createMenu($login)
	{
		$menu = Menu::Create($login);
		$menu->set($this->buildMenu($login));
		$menu->setPosition(50.5, 29);
		$menu->setScale(0.8);
		$menu->show();
	}
	
	public function refreshMenubar()
	{
		// create menu for players
		foreach ($this->storage->players as $login => $player)
			$this->createMenu($login);
		
		// create menu for spectators
		foreach ($this->storage->spectators as $login => $player)
			$this->createMenu($login);
	}
	
	public function onReady()
	{
		$this->enableStorageEvents();
		
		$this->refreshMenubar();
		
		// display menu if player connects
		$this->enableDedicatedEvents();
		
		// enable events for plugins
		$this->enablePluginEvents();
		
		$this->loaded = true;
	}
	
	public function onPlayerConnect($login, $isSpectator)
	{
		// create menu for the player that just joined
		$this->createMenu($login);
	}
	
	protected function buildMenu($login)
	{	
		$menu = array();
		
		// build first menu level
		foreach (self::$menu as $plugin_id => $section)
		{
			$entry = new Item($section['name']);
			$entry->setIcon($section['icon']);
			
			// build second level for buttons
			foreach ($section['buttons'] as $button)
			{
				if (count($section['buttons']) == 1)
				{
					// if this is admin only, then check player belongs to admin group
					if (($button['admin'] && AdminGroup::contains($login))
						|| $button['admin'] === false)
					{
						$entry->setAction($button['action']);
					}
					else
					{
						$entry->setVisibility(false);
					}
				}
				else 
				{
					// if this is admin only, then check player belongs to admin group
					if (($button['admin'] && AdminGroup::contains($login))
						|| $button['admin'] === false)
					{
						$sub = new Subitem($button['name']);
						$sub->setAction($button['action']);
						$entry->addSubitem($sub);
					}
				}
			}
			
			// if menu has any subitems, then show it
			if ($entry->hasSubitems() || $entry->hasAction())
				$menu[] = $entry;
		}
		
		return $menu;
	}
	
	// initializes the menu for a specific plugin
	function initMenu($icon, $plugin_id = null)
	{
		$id = explode('\\', $plugin_id);
		
		$entry = array(
			'name' => end($id),
			'icon' => $icon,
			'buttons' => array()
		);
		
		self::$menu[$plugin_id] = $entry;
		
		if ($this->loaded)
		{
			$this->refreshMenubar();
		}
	}
	
	// adds button for the plugin.
	function addButton($name, $action, $admin = false, $plugin_id = null)
	{
		if (!isset(self::$menu[$plugin_id]))
			$this->initMenu($plugin_id, Icons128x128_1::DefaultIcon);
		
		$button = array(
			'name' => $name,
			'action' => $action,
			'admin' => $admin
		);
		
		self::$menu[$plugin_id]['buttons'][] = $button;
		
		if ($this->loaded)
		{
			$this->refreshMenubar();
		}
	}
	
	function onPluginUnloaded($pluginId)
	{
		// ignore own unloading
		if ($pluginId == $this->getId())
		{
			return;
		}
		
		// remove button from bar
		if (isset(self::$menu[$pluginId]))
		{
			unset(self::$menu[$pluginId]);
		}
		
		// and redraw
		$this->refreshMenubar();
	}
	
	function onUnload()
	{
		Menu::EraseAll();
		
		parent::onUnload();
	}
}

?>