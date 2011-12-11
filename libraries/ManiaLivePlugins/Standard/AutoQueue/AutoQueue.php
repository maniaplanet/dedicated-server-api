<?php
/**
 * AutoQueue plugin - Manage a queue of spectators
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\Standard\AutoQueue;

use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;
use ManiaLive\Features\Admin\AdminGroup;
use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\Standard\AutoQueue\Gui\Windows\Queue;

/**
 * Description of AutoQueue
 */
class AutoQueue extends \ManiaLive\PluginHandler\Plugin
{
	private $queueable = array();
	private $queue = array();
	private $lastActivityTime = array();
	private $mapsPlayed = array();
	
	private $enterQueueAction;
	private $leaveQueueAction;
	
	function onInit()
	{
		$this->setVersion(1);
	}
	
	function onLoad()
	{
		foreach($this->storage->players as $player)
			$this->onPlayerConnect($player->login, false);
		foreach($this->storage->spectators as $spectator)
			$this->onPlayerConnect($spectator->login, true);
		
		$this->enterQueueAction = ActionHandler::getInstance()->createAction(array($this, 'queue'));
		$this->leaveQueueAction = ActionHandler::getInstance()->createAction(array($this, 'unqueue'));
		Queue::Initialize($this->enterQueueAction, $this->leaveQueueAction);
		
		$this->registerChatCommand('queue', 'queue', 0, true);
		$this->registerChatCommand('unqueue', 'unqueue', 0, true);
		
		$this->enableDedicatedEvents(
				ServerEvent::ON_PLAYER_CONNECT
				| ServerEvent::ON_PLAYER_DISCONNECT
				| ServerEvent::ON_PLAYER_INFO_CHANGED
				| ServerEvent::ON_PLAYER_CHAT
				| ServerEvent::ON_PLAYER_CHECKPOINT
				| ServerEvent::ON_PLAYER_INCOHERENCE
				| ServerEvent::ON_PLAYER_FINISH
				| ServerEvent::ON_BEGIN_MATCH
				| ServerEvent::ON_END_MATCH
		);
		$this->enableTickerEvent();
	}
	
	function queue($login)
	{
		if($this->queueable[$login])
		{
			if(array_search($login, $this->queue) === false)
				$this->queue[] = $login;
			if(count($this->mapsPlayed) < $this->storage->server->currentMaxPlayers)
				$this->letQueueFirstPlay();
		}
	}
	
	function unqueue($login)
	{
		$this->queue = array_diff($this->queue, array($login));
	}
	
	private function letQueueFirstPlay()
	{
		$login = array_shift($this->queue);
		if($login)
		{
			$this->connection->forceSpectator($login, 2);
			$this->connection->forceSpectator($login, 0);
			$this->mapsPlayed[$login] = 0;
			Queue::Erase($login);
		}
	}
	
	function onTick()
	{
		$config = Config::getInstance();
		$time = time();
		if($config->playerIdleKick > 0)
			foreach(array_diff(array_keys($this->storage->players), AdminGroup::get()) as $login)
				if($time - $this->lastActivityTime[$login] > $config->playerIdleKick)
					$this->connection->kick($login);
		if($config->spectatorIdleKick > 0)
			foreach(array_diff(array_keys($this->storage->spectators), AdminGroup::get()) as $login)
				if(!in_array($login, $this->queue) && $time - $this->lastActivityTime[$login] > $config->spectatorIdleKick)
					$this->connection->kick($login);
	}
	
	function onPlayerConnect($login, $isSpectator)
	{
		$player = $this->storage->getPlayerObject($login);
		$this->queueable[$login] = $player->ladderScore >= $this->storage->server->ladderServerLimitMin;
		$this->lastActivityTime[$login] = time();
		if($isSpectator)
		{
			$this->connection->forceSpectator($login, 1);
			$this->queue($login);
		}
		else
			$this->mapsPlayed[$login] = 0;
	}
	
	function onPlayerDisconnect($login)
	{
		unset($this->queueable[$login]);
		unset($this->lastActivityTime[$login]);
		if(isset($this->mapsPlayed[$login]))
		{
			unset($this->mapsPlayed[$login]);
			$this->letQueueFirstPlay();
		}
		else
			$this->unqueue($login);
	}
	
	function onPlayerInfoChanged($playerInfo)
	{
		$isSpectator = (bool) ($playerInfo['SpectatorStatus'] % 2);
		$forcedSpectator = $playerInfo['Flags'] % 10;
		$hasPlayerSlot = (bool) (($playerInfo['Flags'] / 1000000) % 10);
		
		$login = $playerInfo['Login'];
		if($isSpectator)
		{
			if($hasPlayerSlot)
			{
				if($forcedSpectator != 1)
				{
					$this->connection->forceSpectator($login, 1);
					unset($this->mapsPlayed[$login]);
				}
				$this->connection->spectatorReleasePlayerSlot($login);
				$this->letQueueFirstPlay();
			}
			if($this->queueable[$login])
			{
				$queue = Queue::Create($login);
				if(in_array($login, $this->queue))
					$queue->setIsQueued();
				else
					$queue->setIsUnqueued();
				$queue->show();
			}
		}
	}
	
	function onBeginMatch($map)
	{
		foreach($this->mapsPlayed as &$nbMaps)
			++$nbMaps;
	}
	
	function onEndMatch($rankings, $map)
	{
		$config = Config::getInstance();
		$freePlaces = $this->storage->server->nextMaxPlayers - count($this->mapsPlayed);
		$nbToKick = max(0, min($config->lastToKick, count($this->queue)) - $freePlaces);
		while($nbToKick > 0 && !empty($rankings))
		{
			$ranking = array_pop($rankings);
			$login = $ranking['Login'];
			if(isset($this->mapsPlayed[$login]) && $this->mapsPlayed[$login] > 0
					&& (!AdminGroup::contains($login) || $config->kickAdmins))
			{
				if($config->queueInsteadOfKick)
				{
					$this->connection->forceSpectator($login, 1);
					$this->queue($login);
				}
				else
					$this->connection->kick($login);
				--$nbToKick;
			}
		}
	}
	
	function onPlayerChat($playerUid, $login, $text, $isRegistredCmd)
	{
		$this->lastActivityTime[$login] = time();
	}
	
	function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex)
	{
		$this->lastActivityTime[$login] = time();
	}
	
	function onPlayerIncoherence($playerUid, $login)
	{
		$this->lastActivityTime[$login] = time();
	}
	
	function onPlayerFinish($playerUid, $login, $timeOrScore)
	{
		$this->lastActivityTime[$login] = time();
	}
	
	function onUnload()
	{
		$this->queueable = array();
		$this->queue = array();
		$this->mapsPlayed = array();
		$this->lastActivityTime = array();
		
		ActionHandler::getInstance()->deleteAction($this->enterQueueAction);
		ActionHandler::getInstance()->deleteAction($this->leaveQueueAction);
		Queue::EraseAll();
		
		parent::onUnLoad();
	}
}

?>
