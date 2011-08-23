<?php

namespace ManiaLivePlugins\Standard\Admin;

use ManiaLivePlugins\Standard\Version;
use ManiaLive\Gui\Windowing\WindowHandler;
use ManiaLive\Gui\Windowing\Windows\Info;
use ManiaLive\Features\Admin\AdminGroup;
use ManiaLivePlugins\Standard\Admin\Gui\Windows\ChooseMode;
use ManiaLib\Gui\Elements\Icons128x128_1;
use ManiaLive\DedicatedApi\Connection;

class Admin extends \ManiaLive\PluginHandler\Plugin
{
	function onInit()
	{
		$this->setVersion(1);
	}
	
	function onLoad()
	{
		$cmd = $this->registerChatCommand('next', 'chatNext', 0, true);
		$cmd->isPublic = false;
		$cmd->help = 'skips current track.';
		
		$cmd = $this->registerChatCommand('restart', 'chatRestart', 0, true);
		$cmd->isPublic = false;
		$cmd->help = 'restarts the current challenge.';
		
		$cmd = $this->registerChatCommand('setmode', 'chatChooseMode', 0, true);
		$cmd->isPublic = false;
		$cmd->help = 'sets new gamemode on the fly, will open a window to make choice.';
		
		$cmd = $this->registerChatCommand('setname', 'chatSetName', 1, true);
		$cmd->isPublic = false;
		$cmd->help = 'sets new server name.';
		
		$cmd = $this->registerChatCommand('setbandwidth', 'chatSetBandwidth', 1, true);
		$cmd->isPublic = false;
		$cmd->help = 'set connection upload and download speed in kbps.';
		
		$cmd = $this->registerChatCommand('setpassword', 'chatSetPassword', 1, true);
		$cmd->isPublic = false;
		$cmd->help = 'Sets the password which will be required to connect and play on the server.';
		
		if ($this->isPluginLoaded('Standard\Menubar'))
		{
			$this->buildMenu();
		}
			
		$this->enablePluginEvents();
	}
	
	function onPluginLoaded($pluginId)
	{
		if ($pluginId == 'Standard\Menubar')
		{
			$this->buildMenu();
		}
	}
	
	function buildMenu()
	{
		$this->callPublicMethod('Standard\Menubar',
			'initMenu',
			Icons128x128_1::Options);
		
		$this->callPublicMethod('Standard\Menubar',
			'addButton',
			'Next Challenge',
			array($this, 'chatNext'),
			true);

		$this->callPublicMethod('Standard\Menubar',
			'addButton',
			'Restart Challenge',
			array($this, 'chatRestart'),
			true);
			
		$this->callPublicMethod('Standard\Menubar',
			'addButton',
			'Change Mode',
			array($this, 'chatChooseMode'),
			true);
	}
	
	function displayWrongState($login)
	{
		$info = Info::Create($login);
		$info->setSize(50, 23);
		$info->setTitle('Bad Server State!');
		$info->setText("This can not be done at the current server state,\nwait a bit and try again!");
		$info->centerOnScreen();
		$info->show();
	}
	
	function chatNext($login)
	{
		if (!AdminGroup::contains($login)) return;
		if ($this->storage->serverStatus->code != 4)
		{
			$this->displayWrongState($login);
			return;
		}
		$this->connection->nextChallenge();
	}
	
	function chatRestart($login)
	{
		if (!AdminGroup::contains($login)) return;
		if ($this->storage->serverStatus->code != 4)
		{
			$this->displayWrongState($login);
			return;
		}
		$this->connection->restartChallenge();
	}
	
	function chatChooseMode($login)
	{
		if (!AdminGroup::contains($login)) return;
		$win = ChooseMode::Create($login);
		$win->centerOnScreen();
		$win->show();
	}
	
	function chatSetName($login, $name)
	{
		if (!AdminGroup::contains($login)) return;
		$this->connection->setServerName($name);
	}
	
	function chatSetBandwidth($login, $speed)
	{
		if (!AdminGroup::contains($login)) return;
		$this->connection->setConnectionRates(intval($speed));
	}
	
	function chatSetPassword($login, $password)
	{
		if (!AdminGroup::contains($login)) return;
		$this->connection->setServerPassword($password);
	}
	
	function onUnload()
	{
		ChooseMode::EraseAll();
		parent::onUnload();
	}
}