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

namespace ManiaLivePlugins\Standard\PluginManager;

use ManiaLivePlugins\Standard\Version;
use ManiaLive\Gui\Windowing\WindowHandler;
use ManiaLive\Gui\Windowing\Windows\Info;
use ManiaLive\Utilities\Console;
use ManiaLive\Threading\Commands\Command;
use ManiaLive\Event\Dispatcher;
use ManiaLive\PluginHandler\PluginHandler;
use ManiaLivePlugins\Standard\PluginManager\Gui\Controls\Plugin;
use ManiaLivePlugins\Standard\PluginManager\Gui\Windows\Manager;
use ManiaLib\Gui\Elements\Icons128x128_1;
use ManiaLive\Features\Admin\AdminGroup;

class PluginManager extends \ManiaLive\PluginHandler\Plugin
{
	protected $availablePlugins;

	function onInit()
	{
		$this->setVersion(0.1);
		
		$this->availablePlugins = array();
	}

	function onLoad()
	{
		$this->createThread();
		$this->sendWorkToOwnThread(new PluginParser(), 'pluginsParsed');
	}

	function onReady()
	{
		$this->enablePluginEvents();
	}

	function pluginsParsed(Command $command)
	{
		$this->availablePlugins = $command->result;
		$this->registerChatCommand('pluginManager', 'openWindow', 0, true, AdminGroup::get());

		if($this->isPluginLoaded('Standard\Menubar', 1.0))
		{
			$this->buildMenu();
		}
	}

	protected function buildMenu()
	{
		$this->callPublicMethod('Standard\Menubar', 'initMenu', Icons128x128_1::Advanced);
		$this->callPublicMethod('Standard\Menubar', 'addButton', 'Manage', array($this, 'openWindow'), true);
	}

	protected function isPluginClassLoaded($class)
	{
		return $this->isPluginLoaded(PluginHandler::getPluginIdFromClass($class));
	}

	function openWindow($login)
	{
		$updates = array();
		
		$window = Manager::Create($login);
		$window->clearPlugins();
		foreach ($this->availablePlugins as $pluginClass)
		{
			$id = PluginHandler::getPluginIdFromClass($pluginClass);
			$plugin = new Plugin($id, $this->isPluginClassLoaded($pluginClass), $pluginClass);
			$plugin->loadCallBack = array($this, 'loadPlugin');
			$plugin->unloadCallBack = array($this, 'unloadPlugin');
			$window->addPlugin($plugin);
		}
		$window->setSize(65, 40);
		$window->centerOnScreen();
		$window->show();
	}

	function loadPlugin($login, $classname)
	{
		$this->connection->chatSendServerMessage('Loading '.$classname, $this->storage->getPlayerObject($login));
		if (!PluginHandler::getInstance()->addPlugin($classname))
		{
			$this->connection->chatSendServerMessage('$900failed to load '.$classname."\nSee logs for more details", $this->storage->getPlayerObject($login));
		}
		$this->openWindow($login);
	}

	function unloadPlugin($login, $classname)
	{
		$this->connection->chatSendServerMessage('Unloading '.$classname, $this->storage->getPlayerObject($login));
		try
		{
			PluginHandler::getInstance()->deletePlugin($classname);
		}
		catch(\Exception $e)
		{
			$this->connection->chatSendServerMessage('$900failed to unload '.$classname."\nSee logs for more details", $this->storage->getPlayerObject($login));
			throw new \Exception($e->getMessage(), $e->getCode(), $e);
		}
		$this->openWindow($login);
	}

	function onPluginLoaded($pluginId)
	{
		$admins = array();
		foreach (AdminGroup::get() as $adminLogin)
		{
			$player = $this->storage->getPlayerObject($adminLogin);
			if($player)
			{
				$admins[] = $player;
			}
		}
		$this->connection->chatSendServerMessage('$0A0plugin '.$pluginId.' has been successfully loaded', $admins);

		if($pluginId == 'Standard\Menubar')
		{
			$this->buildMenu();
		}
	}

	function onPluginUnloaded($pluginId)
	{
		$admins = array();
		foreach (AdminGroup::get() as $adminLogin)
		{
			$player = $this->storage->getPlayerObject($adminLogin);
			if ($player)
			{
				$admins[] = $player;
			}
		}
		$this->connection->chatSendServerMessage('$0A0plugin ' . $pluginId . ' has been successfully unloaded', $admins);
	}
}

?>