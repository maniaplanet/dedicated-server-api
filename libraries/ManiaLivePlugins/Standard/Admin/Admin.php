<?php
/**
 * Admin Plugin - Allow admins to configure server on the fly
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\Standard\Admin;

use ManiaLib\Gui\Elements\Icons128x128_1;
use ManiaLive\Features\Admin\AdminGroup;
use ManiaLive\Gui\Windows\Info;

use ManiaLivePlugins\Standard\Admin\Gui\Windows\ChooseMode;

class Admin extends \ManiaLive\PluginHandler\Plugin
{
	function onInit()
	{
		$this->setVersion(1);
	}
	
	function onLoad()
	{
		$admins = AdminGroup::get();
		
		$cmd = $this->registerChatCommand('next', 'chatNext', 0, true, $admins);
		$cmd->isPublic = false;
		$cmd->help = 'skips current track.';
		
		$cmd = $this->registerChatCommand('restart', 'chatRestart', 0, true, $admins);
		$cmd->isPublic = false;
		$cmd->help = 'restarts the current map.';
		
		$cmd = $this->registerChatCommand('setmode', 'chatChooseMode', 0, true, $admins);
		$cmd->isPublic = false;
		$cmd->help = 'sets new gamemode on the fly, will open a window to make choice.';
		
		$cmd = $this->registerChatCommand('setname', 'chatSetName', 1, true, $admins);
		$cmd->isPublic = false;
		$cmd->help = 'sets new server name.';
		
		$cmd = $this->registerChatCommand('setbandwidth', 'chatSetBandwidth', 1, true, $admins);
		$cmd->isPublic = false;
		$cmd->help = 'set connection upload and download speed in kbps.';
		
		$cmd = $this->registerChatCommand('setpassword', 'chatSetPassword', 1, true, $admins);
		$cmd->isPublic = false;
		$cmd->help = 'Sets the password which will be required to connect and play on the server.';
		
		if($this->isPluginLoaded('Standard\Menubar'))
			$this->buildMenu();
			
		$this->enablePluginEvents();
	}
	
	function onPluginLoaded($pluginId)
	{
		if($pluginId == 'Standard\Menubar')
			$this->buildMenu();
	}
	
	function buildMenu()
	{
		$this->callPublicMethod('Standard\Menubar',
			'initMenu',
			Icons128x128_1::Options);
		
		$this->callPublicMethod('Standard\Menubar',
			'addButton',
			'Next Map',
			array($this, 'chatNext'),
			true);

		$this->callPublicMethod('Standard\Menubar',
			'addButton',
			'Restart Map',
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
		$info->setSize(125, 40);
		$info->setTitle('Bad Server State!');
		$info->setText("This can not be done at the current server state,\nwait a bit and try again!");
		$info->centerOnScreen();
		$info->show();
	}
	
	function chatNext($login)
	{
		if($this->storage->serverStatus->code != 4)
		{
			$this->displayWrongState($login);
			return;
		}
		$this->connection->nextMap();
	}
	
	function chatRestart($login)
	{
		if($this->storage->serverStatus->code != 4)
		{
			$this->displayWrongState($login);
			return;
		}
		$this->connection->restartMap();
	}
	
	function chatChooseMode($login)
	{
		$win = ChooseMode::Create($login);
		$win->centerOnScreen();
		$win->show();
	}
	
	function chatSetName($login, $name)
	{
		$this->connection->setServerName($name);
	}
	
	function chatSetBandwidth($login, $speed)
	{
		$this->connection->setConnectionRates(intval($speed));
	}
	
	function chatSetPassword($login, $password)
	{
		$this->connection->setServerPassword($password);
	}
	
	function onUnload()
	{
		ChooseMode::EraseAll();
		parent::onUnload();
	}
}