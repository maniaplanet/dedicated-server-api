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

use ManiaLive\Data\Event as PlayerEvent;
use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;
use ManiaLive\Features\Admin\AdminGroup;
use ManiaLive\Gui\ActionHandler;
use ManiaLive\Gui\Windows\Dialog;
use ManiaLivePlugins\Standard\AutoQueue\Gui\Windows\FreeSpot;
use ManiaLivePlugins\Standard\AutoQueue\Gui\Windows\Queue;

/**
 * Description of AutoQueue
 */
class AutoQueue extends \ManiaLive\PluginHandler\Plugin
{
	private $queueable = array();
	private $queue = array();
	private $lastWorst = array();
	private $outThisMatch = 0;
	private $lastActivityTime = array();
	private $mapsPlayed = array();
	
	private $enterQueueAction;
	private $leaveQueueAction;
	
	function onInit()
	{
		$this->setVersion('1.1');
	}
	
	function onLoad()
	{
		$this->enterQueueAction = ActionHandler::getInstance()->createAction(array($this, 'queue'));
		$this->leaveQueueAction = ActionHandler::getInstance()->createAction(array($this, 'unqueue'));
		Queue::Initialize($this->enterQueueAction, $this->leaveQueueAction);
		
		foreach($this->storage->players as $player)
			$this->onPlayerConnect($player->login, false);
		foreach($this->storage->spectators as $spectator)
			$this->onPlayerConnect($spectator->login, true);
		
		$this->registerChatCommand('queue', 'queue', 0, true);
		$this->registerChatCommand('unqueue', 'unqueue', 0, true);
		
		$this->enableDedicatedEvents(
				ServerEvent::ON_PLAYER_CONNECT
				| ServerEvent::ON_PLAYER_DISCONNECT
				| ServerEvent::ON_PLAYER_CHAT
				| ServerEvent::ON_PLAYER_CHECKPOINT
				| ServerEvent::ON_PLAYER_INCOHERENCE
				| ServerEvent::ON_PLAYER_FINISH
				| ServerEvent::ON_BEGIN_MAP
				| ServerEvent::ON_END_MAP
		);
		$this->enableStorageEvents(PlayerEvent::ON_PLAYER_CHANGE_SIDE);
		$this->enableTickerEvent();
		$this->connection->setRoundPointsLimit(10);
		$this->connection->setAllWarmUpDuration(1);
	}
	
	function queue($login)
	{
		if($this->queueable[$login])// && !isset($this->mapsPlayed[$login]))
		{
			if(array_search($login, $this->queue) === false)
				$this->queue[] = $login;
			if(count($this->mapsPlayed) < $this->storage->server->currentMaxPlayers)
				$this->letQueueFirstPlay();
			else
			{
				Queue::Add($this->storage->getPlayerObject($login));
				$queue = Queue::Create($login);
				$queue->setIsQueued();
				$queue->show();
			}
		}
	}
	
	function unqueue($login)
	{
		$queuePos = array_search($login, $this->queue);
		if($queuePos !== false)
		{
			unset($this->queue[$queuePos]);
			Queue::Remove($this->storage->getPlayerObject($login));
			$queue = Queue::Create($login);
			$queue->setIsUnqueued();
			$queue->redraw();
		}
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
			Queue::Remove($this->storage->getPlayerObject($login));
			FreeSpot::Create($login)->show();
		}
	}
	
	function onKickDialogClosed($login, Dialog $dialog)
	{
		if($dialog->getAnswer() == Dialog::OK)
			$this->connection->kick($login);
		else
		{
			$this->connection->forceSpectator($login, 2);
			$this->connection->forceSpectator($login, 0);
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
		if(!$this->queueable[$login])
			$this->connection->kick($login);
		$this->lastActivityTime[$login] = time();
		if($isSpectator)
		{
			$this->connection->forceSpectator($login, 1);
			$this->queue($login);
		}
		else
			$this->mapsPlayed[$login] = 0;
	}
	
	function onPlayerDisconnect($login, $disconnectionReason)
	{
		unset($this->queueable[$login]);
		unset($this->lastActivityTime[$login]);
		if(isset($this->mapsPlayed[$login]))
		{
			unset($this->mapsPlayed[$login]);
			$this->letQueueFirstPlay();
			++$this->outThisMatch;
		}
		else
			$this->unqueue($login);
		
		Queue::Erase($login);
		FreeSpot::Erase($login);
	}
	
	function onPlayerChangeSide($player, $oldSide)
	{
		if($player->spectator)
		{
			if($player->hasPlayerSlot)
			{
				try
				{
					$this->connection->spectatorReleasePlayerSlot($player);
				}
				catch(\Exception $e)
				{
					if(in_array($player->login, $this->lastWorst))
					{
						$this->connection->kick($player->login);
						return;
					}
					$dialog = Dialog::Create($player->login, false);
					$dialog->setSize(80, 36);
					$dialog->setTitle('AutoQueue Warning');
					$dialog->setText("There are too many spectators already.\nYou'll be kicked so someone else can play.");
					$dialog->setButtons(Dialog::OK | Dialog::CANCEL);
					$dialog->addCloseCallback(array($this, 'onKickDialogClosed'));
					$dialog->showModal();
					return;
				}
				$this->letQueueFirstPlay();
			}
			$queue = Queue::Create($player->login);
			switch($player->forceSpectator)
			{
				case 0:
					$this->connection->forceSpectator($player, 1);
					unset($this->mapsPlayed[$player->login]);
					$queue->show();
					break;
				case 1:
					$this->queue($player->login);
					$queue->setIsQueued();
					$queue->show();
					break;
			}
		}
	}
	
	function onBeginMap($map, $warmUp, $matchContinuation)
	{
		$this->lastWorst = array();
		$this->outThisMatch = 0;
		foreach($this->mapsPlayed as &$nbMaps)
			++$nbMaps;
	}
	
	function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
	{
		if($wasWarmUp || $restartMap)
			return;
		
		$config = Config::getInstance();
		$freePlaces = $this->storage->server->nextMaxPlayers - count($this->mapsPlayed);
		$nbToKick = max(0, min($config->lastToKick, count($this->queue)) - $this->outThisMatch - $freePlaces);
		while($nbToKick > 0 && !empty($rankings))
		{
			$ranking = array_pop($rankings);
			$login = $ranking['Login'];
			if(isset($this->mapsPlayed[$login]) && $this->mapsPlayed[$login] > 0 && !($config->ignoreAdmins && AdminGroup::contains($login)))
			{
				if($config->queueInsteadOfKick)
				{
					$this->connection->forceSpectator($login, 1);
					$this->lastWorst[] = $login;
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
		$this->lastWorst = array();
		$this->mapsPlayed = array();
		$this->lastActivityTime = array();
		
		ActionHandler::getInstance()->deleteAction($this->enterQueueAction);
		ActionHandler::getInstance()->deleteAction($this->leaveQueueAction);
		Queue::EraseAll();
		Queue::Clear();
		FreeSpot::EraseAll();
		
		parent::onUnload();
	}
}

?>
