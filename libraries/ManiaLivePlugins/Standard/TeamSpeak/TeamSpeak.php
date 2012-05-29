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

use ManiaLib\Utils\Formatting;
use ManiaLive\Application\Event as AppEvent;
use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;
use ManiaLivePlugins\Standard\TeamSpeak\Windows\Main;
use ManiaLivePlugins\Standard\TeamSpeak\Structures\Client;
use ManiaLivePlugins\Standard\TeamSpeak\Structures\Channel;
use ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Adapter\ServerQuery\Event as TSEvent;
use ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\Signal;

/**
 * Description of TeamSpeak
 */
class TeamSpeak extends \ManiaLive\PluginHandler\Plugin
{
	const BASIC_POWER = 0x1337;
	
	private $tsConnection;
	private $tick = 0;
	
	function onInit()
	{
		$this->setVersion('2.0');
	}
	
	function onLoad()
	{
		$config = Config::getInstance();
		if(!$config->serverChannelName)
			$config->serverChannelName = substr('ManiaPlanet> '.Formatting::stripStyles($this->storage->server->name), 0, 40);
		
		$this->tsConnection = Connection::getInstance();
		$this->tsConnection->open();
		if(!$this->tsConnection->isConnected())
			$this->enableTickerEvent();
		
		Signal::getInstance()->subscribe('notifyEvent', array($this, 'onTeamSpeakEvent'));
		$this->enableDedicatedEvents(ServerEvent::ON_PLAYER_CONNECT | ServerEvent::ON_PLAYER_DISCONNECT | ServerEvent::ON_PLAYER_INFO_CHANGED);
	}
	
	function onReady()
	{
		foreach($this->storage->players as $player)
			$this->onPlayerConnect($player->login, true);
		foreach($this->storage->spectators as $player)
			$this->onPlayerConnect($player->login, false);
		
		if($this->tsConnection->isConnected())
			$this->enableApplicationEvents(AppEvent::ON_PRE_LOOP);
	}
	
	function onTeamSpeakEvent(TSEvent $event)
	{
		$data = $event->getData();
		switch($event->getType()->toString())
		{
			case 'cliententerview':
				// We don't want query clients
				if($data['client_type'])
					break;
				
				$client = new Client($data, $this->tsConnection->getCustomInfo($data));
				$this->tsConnection->useToken($client->login);
				$player = $this->storage->getPlayerObject($client->login);
				if(Channel::IsClientAllowed($client, $client->channelId))
				{
					if($client->channelId == Channel::$serverIds[Channel::COMMENTS] && $client->isCommentator)
						$this->tsConnection->toggleClientComment($client->clientId, true);
				}
				else
				{
					$teamId = $player ? $player->teamId : -1;
					$this->tsConnection->moveClient($client->clientId, Channel::$serverIds[$teamId]);
				}
				
				if($player)
				{
					$main = Main::Create($client->login);
					$main->setConnected($client->channelId, $player->teamId);
					$main->show();
				}
				break;
			
			case 'clientmoved':
				if( ($client = Client::GetById((int) $data['clid'])) )
				{
					$newChannelId = (int) $data['ctid'];
					if(Channel::IsClientAllowed($client, $newChannelId))
					{
						$client->channelId = $newChannelId;
						if($client->channelId == Channel::$serverIds[Channel::COMMENTS] && $client->isCommentator)
							$this->tsConnection->toggleClientComment($client->clientId, true);
						
						if( ($player = $this->storage->getPlayerObject($client->login)) )
						{
							$main = Main::Create($client->login);
							$main->setConnected($client->channelId, $player->teamId);
							$main->redraw();
						}
					}
					else
						$this->tsConnection->moveClient($client->clientId, $client->channelId);
				}
				break;
				
			case 'clientleftview':
				if( ($client = Client::GetById((int) $data['clid'])) )
				{
					Client::EraseById($client->clientId);
					if($client->login && $this->storage->getPlayerObject($client->login))
					{
						$main = Main::Create($client->login);
						$main->setNotConnected();
						$main->redraw();
					}
				}
				break;
			
			case 'channelcreated':
				$channel = new Channel($data, $this->tsConnection->getChannelPermissionList($data));
				if($channel == Channel::GetDefault() || $channel->parentId == Channel::GetDefault()->channelId)
					$this->onReady();
				break;
				
			case 'channelmoved':
				if( ($channel = Channel::Get((int) $data['cid'])) )
					$channel->parentId = $data['cpid'];
				break;
				
			case 'channeldeleted':
				if( ($channel = Channel::Get((int) $data['cid'])) )
					Channel::Erase($channel->channelId);
				break;
		}
	}
	
	function onPlayerConnect($login, $isSpectator)
	{
		$main = Main::Create($login);
		if( ($client = Client::GetByLogin($login)) )
		{
			$teamId = $this->storage->getPlayerObject($login)->teamId;
			$main->setConnected($client->channelId, $teamId);
			if(!Channel::IsClientAllowed($client, $client->channelId))
				$this->tsConnection->moveClient($client->clientId, Channel::$serverIds[$teamId]);
		}
		else if($this->tsConnection->isConnected())
			$main->setNotConnected();
		else
			$main->setError();
		$main->show();
	}
	
	function onPlayerDisconnect($login)
	{
		$this->movePlayerIFN($login);
		$this->tsConnection->deleteToken($login);
	}
	
	function onPlayerInfoChanged($playerInfo)
	{
		$this->movePlayerIFN($playerInfo['Login']);
	}
	
	private function movePlayerIFN($login)
	{
		if( ($client = Client::GetByLogin($login)) )
		{
			$player = $this->storage->getPlayerObject($login);
			if(!Channel::IsClientAllowed($client, $client->channelId))
				$this->tsConnection->moveClient($client->clientId, Channel::$serverIds[$player->teamId]);
		}
	}
	
	function onTick()
	{
		if(++$this->tick % 10 == 0)
		{
			$this->tsConnection->open();
			if($this->tsConnection->isConnected())
			{
				$this->disableTickerEvent();
				$this->onReady();
			}
		}
	}
	
	function onPreLoop()
	{
		if(!$this->tsConnection->isConnected())
		{
			Channel::EraseAll();
			Client::EraseAll();
			foreach(Main::GetAll() as $main)
			{
				$main->setError();
				$main->redraw();
			}
			$this->disableApplicationEvents();
			$this->enableTickerEvent();
		}
	}
		
	function onUnload()
	{
		Main::EraseAll();
		Channel::EraseAll();
		Client::EraseAll();
		$this->tsConnection->close();
	}
}

?>