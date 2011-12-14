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

use ManiaLib\Utils\TMStrings;
use ManiaLive\Features\Admin\AdminGroup;
use ManiaLive\Gui\Group;
use ManiaLive\Application\Event as AppEvent;
use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;
use ManiaLivePlugins\Standard\TeamSpeak\Gui\Windows\ClientList;
use ManiaLivePlugins\Standard\TeamSpeak\Gui\Windows\ChannelTree;
use ManiaLivePlugins\Standard\TeamSpeak\Gui\Windows\Main;
use ManiaLivePlugins\Standard\TeamSpeak\Structures\Client;
use ManiaLivePlugins\Standard\TeamSpeak\Structures\Channel;
use ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Adapter\ServerQuery\Event as TSEvent;
use ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\Signal;

/**
 * Description of TeamSpeak
 */
class TeamSpeak extends \ManiaLive\PluginHandler\Plugin
{
	const COMMENTATOR = 0x1337;
	
	private $tsConnection;
	private $defaultChannelId;
	private $tick = 0;
	
	function onInit()
	{
		$this->setVersion('0.1');
	}
	
	function onLoad()
	{
		$config = Config::getInstance();
		if($config->useLangChannels || !$config->listAllChannels)
			$config->useDedicatedChannel = true;
		if(!$config->dedicatedChannelName)
			$config->dedicatedChannelName = substr('ManiaPlanet> '.TMStrings::stripAllTmStyle($this->storage->server->name), 0, 40);
		
		$this->tsConnection = Connection::getInstance();
		$this->tsConnection->open();
		if(!$this->tsConnection->isConnected())
			$this->enableTickerEvent();
		
		Signal::getInstance()->subscribe('notifyEvent', array($this, 'onTeamSpeakEvent'));
		$this->enableDedicatedEvents(ServerEvent::ON_PLAYER_CONNECT | ServerEvent::ON_PLAYER_DISCONNECT);
	}
	
	function onReady()
	{
		foreach(Client::GetAll() as $client)
			ClientList::Add($client);
		$this->defaultChannelId = ($channel = Channel::GetDefault()) ? $channel->channelId : -1;
		foreach(Channel::GetAll() as $channel)
			if($channel->hasToBeListed)
				ChannelTree::Add($channel);
		
		foreach($this->storage->players as $player)
			$this->onPlayerConnect($player->login, true);
		foreach($this->storage->spectators as $player)
			$this->onPlayerConnect($player->login, false);
		
		if($this->tsConnection->isConnected())
		{
			ClientList::Create(Group::Create('admin', AdminGroup::get()));
			$this->enableApplicationEvents(AppEvent::ON_PRE_LOOP);
		}
	}
	
	function onTeamSpeakEvent(TSEvent $event)
	{
		$config = Config::getInstance();
		$data = $event->getData();
		switch($event->getType()->toString())
		{
			case 'cliententerview':
				// We don't want query clients
				if($data['client_type'])
					break;
				
				$client = Client::CreateFromTeamSpeak($data);
				ClientList::Add($client);
				if($client->login)
				{
					if(!$client->isCommentator && in_array($client->login, Config::getInstance()->commentators))
						$this->tsConnection->toggleClientComment(null, $client->clientId, true);
					ClientList::Create($client->login, true, $client->channelId);
					$main = Main::Create($client->login);
					$main->setConnected();
					$main->setDefaultButtonText(Channel::Get($client->channelId)->name);
					$main->show();
					$main->toggleClientList($client->login);
				}
				break;
			
			case 'clientmoved':
				if( ($client = Client::Get((int) $data['clid'])) )
				{
					ClientList::Remove($client);
					$client->update($data);
					ClientList::Add($client);
					if($client->isCommentator)
						$this->tsConnection->toggleClientComment(null, $client->clientId, true);
					if($client->login && $this->storage->getPlayerObject($client->login))
					{
						$main = Main::Create($client->login);
						$main->setDefaultButtonText(Channel::Get($client->channelId)->name);
						$main->redraw();
					}
				}
				break;
				
			case 'clientleftview':
				if( ($client = Client::Get((int) $data['clid'])) )
				{
					ClientList::Remove($client);
					Client::Erase($client->clientId);
					if($client->login && $this->storage->getPlayerObject($client->login))
					{
						$main = Main::Create($client->login);
						$main->setNotConnected();
						$defaultChannelName = Config::getInstance()->getPlayerDefaultChannel($this->storage->getPlayerObject($client->login));
						if($defaultChannelName)
							$main->setDefaultButtonText($defaultChannelName);
						else
							$main->setDefaultButtonText('Connect');
						$main->hideClientList($client->login);
						$main->redraw();
						ClientList::Erase($client->login);
						foreach(ChannelTree::Get($client->login) as $channelTree)
							$channelTree->redraw();
					}
				}
				break;
			
			case 'channelcreated':
				$channel = Channel::CreateFromTeamSpeak($data);
				if($channel->hasToBeListed)
					ChannelTree::Add($channel);
				if($channel === Channel::GetDefault())
				{
					$this->defaultChannelId = $channel->channelId;
					if($config->useLangChannels)
						$this->tsConnection->createLangChannels($channel);
				}
				break;
			
			case 'channeledited':
				if( ($channel = Channel::Get((int) $data['cid'])) )
				{
					$commentatorWasEnabled = $channel->commentatorEnabled;
					$channel->update($data);
					if(!$commentatorWasEnabled && $channel->commentatorEnabled)
						foreach($channel->clients as $client)
							if($client->isCommentator)
								$this->tsConnection->toggleClientComment(null, $client->clientId, true);
				}
				break;
				
			case 'channelmoved':
				if( ($channel = Channel::Get((int) $data['cid'])) )
				{
					$wasListed = $channel->hasToBeListed;
					$channel->update($data);
					if($wasListed)
					{
						if($channel->hasToBeListed)
							ChannelTree::Move($channel);
						else
							ChannelTree::Remove($channel);
					}
					else if($channel->hasToBeListed)
						ChannelTree::Add($channel);
				}
				break;
				
			case 'channeldeleted':
				if( ($channel = Channel::Get((int) $data['cid'])) )
				{
					ChannelTree::Remove($channel);
					Channel::Erase($channel->channelId);
				}
				break;
		}
	}
	
	function onPlayerConnect($login, $isSpectator)
	{
		$main = Main::Create($login);
		if( ($client = Client::GetByLogin($login)) )
		{
			$main->setConnected();
			$main->setDefaultButtonText(Channel::Get($client->channelId)->name);
				ClientList::Create($login, true, $client->channelId);
		}
		else
		{
			$defaultChannelName = Config::getInstance()->getPlayerDefaultChannel($this->storage->getPlayerObject($login));
			if($defaultChannelName)
				$main->setDefaultButtonText($defaultChannelName);
			if($this->tsConnection->isConnected())
				$main->setNotConnected();
			else
				$main->setError();
		}
		$main->show();
	}
	
	function onPlayerDisconnect($login)
	{
		Main::Erase($login);
		ClientList::Erase($login);
		ChannelTree::Erase($login);
		if( ($client = Client::GetByLogin($login)) )
		{
			$client->nicknameToShow = $client->nickname;
			$client->notifyObservers();
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
			ChannelTree::EraseAll();
			ClientList::EraseAll();
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
		ChannelTree::EraseAll();
		ClientList::EraseAll();
		Main::EraseAll();
		Channel::EraseAll();
		Client::EraseAll();
		$this->tsConnection->close();
	}
}

?>