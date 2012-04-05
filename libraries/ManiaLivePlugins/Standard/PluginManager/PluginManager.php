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

use ManiaLib\Gui\Elements\Icons128x128_1;
use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;
use ManiaLive\Features\Admin\AdminGroup;
use ManiaLive\PluginHandler\PluginHandler;
use ManiaLive\Threading\Command;
use ManiaLive\Threading\ThreadHandler;
use ManiaLivePlugins\Standard\PluginManager\Gui\Controls\Plugin;
use ManiaLivePlugins\Standard\PluginManager\Gui\Windows\Manager;

class PluginManager extends \ManiaLive\PluginHandler\Plugin
{
	private $connectedAdmins = array();
	private $threadId;

	function onInit()
	{
		$this->setVersion(1);
	}

	function onLoad()
	{
		$this->threadId = ThreadHandler::getInstance()->launchThread();
		ThreadHandler::getInstance()->addTask($this->threadId, new PluginParser(), array($this, 'pluginsParsed'));
		
		$this->enableDedicatedEvents(ServerEvent::ON_PLAYER_CONNECT | ServerEvent::ON_PLAYER_DISCONNECT);
	}

	function onReady()
	{
		$this->enablePluginEvents();
		
		foreach($this->storage->players as $player)
			$this->onPlayerConnect($player->login, false);
		foreach($this->storage->spectators as $player)
			$this->onPlayerConnect($player->login, true);
	}

	function pluginsParsed(Command $command)
	{
		ThreadHandler::getInstance()->killThread($this->threadId);
		$this->threadId = null;
		
		foreach($command->getResult() as $pluginId)
			Manager::AddPlugin($pluginId, $this);
		
		$this->registerChatCommand('pluginManager', 'openWindow', 0, true, AdminGroup::get());
		$this->setPublicMethod('openWindow');
		if($this->isPluginLoaded('Standard\Menubar', 1))
			$this->buildMenu();
	}

	protected function buildMenu()
	{
		$this->callPublicMethod('Standard\Menubar', 'initMenu', Icons128x128_1::Advanced);
		$this->callPublicMethod('Standard\Menubar', 'addButton', 'Manage', array($this, 'openWindow'), true);
	}

	function openWindow($login)
	{
		$window = Manager::Create($login);
		$window->setSize(160, 100);
		$window->centerOnScreen();
		$window->show();
	}

	function loadPlugin($login, $pluginClass)
	{
		$this->connection->chatSendServerMessage('Loading '.$pluginClass, $login);
		if(!PluginHandler::getInstance()->load($pluginClass))
			$this->connection->chatSendServerMessage('$900failed to load '.$pluginClass."\nSee logs for more details", $login);
	}

	function unloadPlugin($login, $classname)
	{
		$this->connection->chatSendServerMessage('Unloading '.$classname, $login);
		try
		{
			PluginHandler::getInstance()->unload($classname);
		}
		catch(\Exception $e)
		{
			$this->connection->chatSendServerMessage('$900failed to unload '.$classname."\nSee logs for more details", $login);
			throw new \Exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	function onPluginLoaded($pluginId)
	{
		$this->connection->chatSendServerMessage('$0A0plugin '.$pluginId.' has been successfully loaded', array_keys($this->connectedAdmins));
		$plugin = Manager::GetPlugin($pluginId);
		if($plugin)
			$plugin->setIsLoaded(true);

		if($pluginId == 'Standard\Menubar')
			$this->buildMenu();
	}

	function onPluginUnloaded($pluginId)
	{
		$this->connection->chatSendServerMessage('$0A0plugin '.$pluginId.' has been successfully unloaded', array_keys($this->connectedAdmins));
		$plugin = Manager::GetPlugin($pluginId);
		if($plugin)
			$plugin->setIsLoaded(false);
	}
	
	function onPlayerConnect($login, $isSpectator)
	{
		if(AdminGroup::contains($login))
			$this->connectedAdmins[$login] = true;
	}
	
	function onPlayerDisconnect($login)
	{
		if(AdminGroup::contains($login))
			unset($this->connectedAdmins[$login]);
	}
	
	function onUnload()
	{
		parent::onUnload();
		Manager::ErasePlugins();
		if($this->threadId)
			ThreadHandler::getInstance()->killThread($this->threadId);
	}
}

?>