<?php
/**
 * TeamSpeak Plugin - Connect to a TeamSpeak 3 server
 * Original work by refreshfr
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\Standard\TeamSpeak;

use ManiaLive\Event\Dispatcher;
use ManiaLive\Application\Listener as AppListener;
use ManiaLive\Application\Event as AppEvent;
use ManiaLive\Features\Tick\Listener as TickListener;
use ManiaLive\Features\Tick\Event as TickEvent;
use ManiaLive\Threading\ThreadHandler;
use ManiaLivePlugins\Standard\TeamSpeak\Structures\Client;
use ManiaLivePlugins\Standard\TeamSpeak\Structures\Channel;
use ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\TeamSpeak3;

/**
 * Description of Connection
 */
class Connection extends \ManiaLib\Utils\Singleton implements AppListener, TickListener
{
	private $server;
	private $playersGroupId;
	private $privilegeKeys = array();
	
	private $processHandler;
	private $processId;
	
	private $tick = 0;
	
	function open()
	{
		$config = Config::getInstance();
		try
		{
			$this->server = TeamSpeak3::factory('serverquery://'.$config->queryLogin.':'.$config->queryPassword.'@'.$config->queryHost.':'.$config->queryPort.'/?server_port='.$config->voicePort.'&blocking=0#no_query_clients');
			$this->server->execute('instanceedit', array(
				'serverinstance_serverquery_flood_commands' => 20,
				'serverinstance_serverquery_flood_time' => 1,
				'serverinstance_serverquery_flood_ban_time' => 1));
		}
		catch(\Exception $e)
		{
			$this->server = null;
			return;
		}
		
		// Threading is really useful for this plugin !!!
		$this->processHandler = ThreadHandler::getInstance();
		$this->processId = $this->processHandler->launchThread();
		
		// Enable events (from ML and from TS)
		Dispatcher::register(AppEvent::getClass(), $this, AppEvent::ON_PRE_LOOP);
		Dispatcher::register(TickEvent::getClass(), $this);
		$this->server->notifyRegister('channel');
		
		// Find players group id or create a new group for privilege keys
		foreach($this->server->serverGroupList() as $group)
		{
			if($group['name'] == 'ManiaPlanet Player')
			{
				$this->playersGroupId = $group['sgid'];
				break;
			}
		}
		$this->server->serverGroupListReset();
		if(!$this->playersGroupId)
			$this->playersGroupId = $this->server->serverGroupCreate('ManiaPlanet Player');
		
		$this->server->serverGroupPermAssign($this->playersGroupId, 'b_channel_join_semi_permanent', 1);
		
		// Populate
		foreach($this->server->channelList() as $channel)
			new Channel($channel, $this->getChannelPermissionList($channel));
		foreach($this->server->clientList() as $client)
			new Client($client, $this->getCustomInfo($client));
		
		// Handle default channels
		$this->createChannelsIFN();
	}
	
	function isConnected()
	{
		return $this->server != null;
	}
	
	function createChannelsIFN()
	{
		$config = Config::getInstance();
		if(Channel::$serverIds[Channel::FREE_TALK])
		{
			if(!Channel::$serverIds[Channel::COMMENTS])
				$this->processHandler->addTask(
						$this->processId,
						new Tasks\ChannelCreate($config, 'Comments', Channel::$serverIds[Channel::FREE_TALK], TeamSpeak::BASIC_POWER)
				);
			
			if(!Channel::$serverIds[Channel::TEAM_1])
				$this->processHandler->addTask(
						$this->processId,
						new Tasks\ChannelCreate($config, 'Team 1', Channel::$serverIds[Channel::FREE_TALK], 0, TeamSpeak::BASIC_POWER),
						array($this, 'onChannelCreated')
				);
			
			if(!Channel::$serverIds[Channel::TEAM_2])
				$this->processHandler->addTask(
						$this->processId,
						new Tasks\ChannelCreate($config, 'Team 2', Channel::$serverIds[Channel::FREE_TALK], 0, TeamSpeak::BASIC_POWER+1),
						array($this, 'onChannelCreated')
				);
		}
		else
			$this->processHandler->addTask(
					$this->processId,
					new Tasks\ChannelCreate($config, $config->serverChannelName),
					array($this, 'createChannelsIFN')
			);
	}
	
	// Hack because we can't set needed join power at channel creation
	// and no event is launched when needed join power is modified...
	function onChannelCreated($command)
	{
		$channelId = $command->getResult();
		$channel = $this->server->request('channelinfo cid='.$channelId)->toList();
		$channel['cid'] = $channelId;
		new Channel($channel, $this->getChannelPermissionList($channel));
	}
	
	function getCustomInfo($client)
	{
		try
		{
			$infoArray = $this->server->customInfo($client['client_database_id']);
			$customInfo = array();
			foreach($infoArray as $info)
				$customInfo[strval($info['ident'])] = strval($info['value']);
			
			return $customInfo;
		}
		catch(\Exception $e)
		{
			return array();
		}
	}
	
	function getChannelPermissionList($channel)
	{
		try
		{
			return $this->server->channelPermList($channel['cid'], true);
		}
		catch(\Exception $e)
		{
			return array();
		}
	}
	
	function getToken($login)
	{
		if(!isset($this->privilegeKeys[$login]))
			$this->privilegeKeys[$login] = $this->server->privilegeKeyCreate(
					TeamSpeak3::TOKEN_SERVERGROUP, $this->playersGroupId, 0,
					'Created by ManiaLive to authenticate players',
					'ident=maniaplanet_login value='.$login);
		return $this->privilegeKeys[$login];
	}
	
	function useToken($login)
	{
		if(isset($this->privilegeKeys[$login]))
			unset($this->privilegeKeys[$login]);
	}
	
	function deleteToken($login)
	{
		if(isset($this->privilegeKeys[$login]))
		{
			$this->server->privilegeKeyDelete($this->privilegeKeys[$login]);
			unset($this->privilegeKeys[$login]);
		}
	}
	
	function moveClient($clientId, $channelId)
	{
		$this->processHandler->addTask(
				$this->processId,
				new Tasks\ClientMove(Config::getInstance(), $clientId, $channelId)
		);
	}
	
	function movePlayer($login, $channelId)
	{
		$client = Client::GetByLogin($login);
		if($client)
			$this->moveClient($client->clientId, $channelId);
	}
	
	function toggleClientComment($clientId, $enable=true)
	{
		$this->processHandler->addTask(
				$this->processId,
				new Tasks\ClientToggleComment(Config::getInstance(), $clientId, $enable)
		);
	}
	
	function close($onPurpose=true)
	{
		if($this->server)
		{
			if($onPurpose)
			{
				$this->server->notifyUnregister();
				foreach($this->privilegeKeys as $key)
					$this->server->privilegeKeyDelete($key);
				$this->privilegeKeys = array();
			}
			$this->server->clientListReset();
			$this->server->channelListReset();
			$this->server = null;
			$this->playersGroupId = 0;
		}
		if($this->processHandler && $this->processId)
		{
			$this->processHandler->killThread($this->processId);
			$this->processId = null;
		}
		Dispatcher::unregister(AppEvent::getClass(), $this);
		Dispatcher::unregister(TickEvent::getClass(), $this);
		// TODO something to avoid memory leaks when unloading the plugin
		// but the TS framework is f*cked up (plenty of circular references
		// which can't be unset from outside...)
	}
	
	// Events
	function onTick()
	{
		if(++$this->tick % 5 == 0)
		{
			try
			{
				if($this->tick % 60 == 0)
				{
					$this->server->request('whoami');
					$this->processHandler->addTask(
							$this->processId,
							new Tasks\ConnectionKeepAlive(Config::getInstance())
					);
				}
			}
			catch(\Exception $e)
			{
				$this->close(false);
			}
		}
	}
	
	function onPreLoop()
	{
		// Receiving TeamSpeak events
		try
		{
			$this->server->getAdapter()->readNotifications();
		}
		catch(\Exception $e)
		{
			$this->close(false);
		}
	}
	
	function onInit() {}
	function onRun() {}
	function onPostLoop() {}
	function onTerminate() {}
}

?>