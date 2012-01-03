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

use ManiaLive\Cache\Cache;
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
	static public $languages = array
	(
		'ar' => 'Arabic',
        'cz' => 'Čeština',
        'da' => 'Dansk',
        'de' => 'Deutsch',
        'el' => 'Ελληνικά',
        'en' => 'English',
        'es' => 'Español',
        'et' => 'Eesti',
        'fi' => 'Suomi',
        'fr' => 'Français',
        'hu' => 'Magyar',
        'it' => 'Italiano',
        'jp' => '日本語',
        'kr' => '한국어',
        'lv' => 'Latviešu',
        'nb' => 'Norsk',
        'nl' => 'Nederlands',
        'pl' => 'Polski',
        'pt' => 'Português',
        'ro' => 'Română',
        'ru' => 'Русский',
        'sk' => 'Slovenčina',
        'sv' => 'Svenska',
        'tr' => 'Türkçe',
        'zh' => '中文'
	);
	
	private $server;
	private $processHandler;
	private $processId;
	
	private $tick = 0;
	
	function open()
	{
		$config = Config::getInstance();
		try
		{
			$this->server = TeamSpeak3::factory('serverquery://'.$config->queryLogin.':'.$config->queryPassword.'@'.$config->ipAddress.':'.$config->queryPort.'/?server_port='.$config->voicePort.'&blocking=0#no_query_clients');
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
		
		// Populate
		foreach($this->server->channelList() as $channel)
			Channel::CreateFromTeamSpeak($channel->getInfo());
		foreach($this->server->clientList() as $client)
			Client::CreateFromTeamSpeak($client->getInfo());
		
		// Handle default channels
		if($config->useDedicatedChannel)
		{
			if( !($defaultChannel = Channel::GetDefault()) )
				$this->processHandler->addTask(
						$this->processId,
						new Tasks\ChannelCreate($config, $config->dedicatedChannelName)
				);
			else if($config->useLangChannels)
				$this->createLangChannels($defaultChannel);
		}
	}
	
	function isConnected()
	{
		return $this->server != null;
	}
	
	function createLangChannels($parentChannel)
	{
		$wantedChannels = array_values(self::$languages);
		$existingChannels = array_map(function ($subChannel) { return $subChannel->name; }, $parentChannel->subChannels);
		foreach(array_diff($wantedChannels, $existingChannels) as $channelName)
			$this->processHandler->addTask(
					$this->processId,
					new Tasks\ChannelCreate(Config::getInstance(), $channelName, $parentChannel->channelId)
			);
		
	}
	
	function movePlayer($login, $channelId)
	{
		if( ($client = Client::GetByLogin($login)) )
			$this->processHandler->addTask(
					$this->processId,
					new Tasks\ClientMove(Config::getInstance(), $client->clientId, $channelId)
			);
	}
	
	function toggleGlobalComment($login, $enable)
	{
		$config = Config::getInstance();
		$defaultId = Channel::GetDefault() ? Channel::GetDefault()->channelId : -1;
		foreach(Channel::GetAll() as $channel)
			if($channel->hasToBeListed)
				$this->processHandler->addTask(
						$this->processId,
						new Tasks\ChannelToggleComment(Config::getInstance(), $channel->channelId, $enable)
				);
	}
	
	function toggleChannelComment($login, $channelId, $enable)
	{
		if( ($channel = Channel::Get($channelId)) )
			$this->processHandler->addTask(
					$this->processId,
					new Tasks\ChannelToggleComment(Config::getInstance(), $channelId, $enable)
			);
	}
	
	function toggleClientComment($login, $clientId, $enable)
	{
		if( ($client = Client::Get($clientId)) )
		{
			$client->isCommentator = $enable;
			$client->notifyObservers();
			if( ($channel = Channel::Get($client->channelId)) && $channel->commentatorEnabled)
				$this->processHandler->addTask(
						$this->processId,
						new Tasks\ClientToggleComment(Config::getInstance(), $clientId, $enable)
				);
		}
	}
	
	function close($isDisconnected=false)
	{
		if($this->server)
		{
			if(!$isDisconnected)
				$this->server->notifyUnregister();
			$this->server->clientListReset();
			$this->server->channelListReset();
			$this->server = null;
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
	function onClientEdited($command)
	{
		print_r($command->result);
		list($clientId, $isCommentator) = $command->result;
		if( ($client = Client::Get($clientId)) )
		{
			$client->isCommentator = (bool) $isCommentator;
			$client->notifyObservers();
		}
	}
	
	function onTick()
	{
		if(++$this->tick % 5 == 0)
		{
			try
			{
				foreach(Client::GetAll() as $client)
					$client->update($this->server->request('clientinfo clid='.$client->clientId)->toList());

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
				$this->close(true);
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
			$this->close(true);
		}
	}
	
	function onInit() {}
	function onRun() {}
	function onPostLoop() {}
	function onTerminate() {}
}

?>