<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLive\Data;

use ManiaLib\Utils\TMStrings;
use ManiaLive\Event\Dispatcher;
use ManiaLive\Application\Listener as AppListener;
use ManiaLive\Application\Event as AppEvent;
use ManiaLive\DedicatedApi\Callback\Listener as ServerListener;
use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;
use ManiaLive\DedicatedApi\Connection;
use ManiaLive\DedicatedApi\Structures\GameInfos;
use ManiaLive\DedicatedApi\Structures\Map;
use ManiaLive\DedicatedApi\Structures\Player;
use ManiaLive\DedicatedApi\Structures\Vote;
use ManiaLive\Utilities\Console;
use ManiaLive\Application\SilentCriticalEventException;
use ManiaLive\Application\CriticalEventException;

/**
 * Contain every important data about the server
 */
class Storage extends \ManiaLib\Utils\Singleton implements ServerListener, AppListener {

	private $disconnectedPlayers = array();
	/**
	 * Player's checkpoints
	 */
	private $checkpoints = array();
	/**
	 * Contains Player object. It represents the player connected to the server
	 * @var \ManiaLive\DedicatedApi\Structures\Player[]
	 */
	public $players = array();
	/**
	 * Contains Player object. It represents the spectators connected to the server
	 * @var \ManiaLive\DedicatedApi\Structures\Player[]
	 */
	public $spectators = array();
	/**
	 * Contains Player object. It represents the current ranking on the server
	 * @var \ManiaLive\DedicatedApi\Structures\Player[]
	 */
	public $ranking = array();
	/**
	 * Contains Map objects. It represents the current maps available on the server
	 * @var \ManiaLive\DedicatedApi\Structures\Map[]
	 */
	public $maps;
	/**
	 * Represents the current Map object
	 * @var \ManiaLive\DedicatedApi\Structures\Map
	 */
	public $currentMap;
	/**
	 * Represents the next Map object
	 * @var \ManiaLive\DedicatedApi\Structures\Map
	 */
	public $nextMap;
	/**
	 * Represents the Current Server Options
	 * @var \ManiaLive\DedicatedApi\Structures\ServerOptions
	 */
	public $server;
	/**
	 * Represents the Current Game Infos
	 * @var \ManiaLive\DedicatedApi\Structures\GameInfos
	 */
	public $gameInfos;
	/**
	 * Represents the current Server Status
	 * @var \ManiaLive\DedicatedApi\Structures\Status
	 */
	public $serverStatus;
	/**
	 * Contains the server login
	 * @var string
	 */
	public $serverLogin;
	/**
	 * Contains the current vote
	 * @var Vote
	 */
	public $currentVote;
	private $isWarmUp = false;

	protected function __construct()
	{
		Dispatcher::register(AppEvent::getClass(), $this, AppEvent::ON_INIT | AppEvent::ON_POST_LOOP);
		Dispatcher::register(ServerEvent::getClass(), $this, ServerEvent::ALL);
	}

	#region Implementation de l'applicationListener

	function onInit()
	{
		$connection = Connection::getInstance();
		$this->serverStatus = $connection->getStatus();

		$players = $connection->getPlayerList(-1, 0);
		foreach($players as $player)
		{
			try
			{
				$details = $connection->getDetailedPlayerInfo($player->login);

				foreach($details as $key => $value)
				{
					if($value)
					{
						$param = lcfirst($key);
						$player->$param = $value;
					}
				}

				if($player->spectatorStatus % 10 == 0)
					$this->players[$player->login] = $player;
				else
					$this->spectators[$player->login] = $player;
			}
			catch(\Exception $e) {}
		}

		$this->maps = $connection->getMapList(-1, 0);
		$nextIndex = $connection->getNextMapIndex();
		$this->nextMap = $this->maps[$nextIndex];
		$this->currentMap = $connection->getCurrentMapInfo();

		$this->server = $connection->getServerOptions();
		$this->gameInfos = $connection->getCurrentGameInfo();
		$this->serverLogin = $connection->getMainServerPlayerInfo()->login;

		Console::printlnFormatted('Current map: ' . TMStrings::stripAllTmStyle($this->currentMap->name));
	}

	function onPostLoop()
	{
		foreach($this->disconnectedPlayers as $login)
		{
			if(isset($this->spectators[$login]) && !$this->spectators[$login]->isConnected)
				unset($this->spectators[$login]);
			else if(isset($this->players[$login]) && !$this->players[$login]->isConnected)
				unset($this->players[$login]);
		}
		$this->disconnectedPlayers = array();

		if($this->currentVote instanceof Vote && $this->currentVote->status != Vote::STATE_NEW)
			$this->currentVote = null;
	}

	function onRun() {}
	function onPreLoop() {}
	function onTerminate() {}

	#endRegion
	#region Implementation of DedicatedApi\Listener

	function onPlayerConnect($login, $isSpectator)
	{
		try
		{
			$playerInfos = Connection::getInstance()->getPlayerInfo($login, 1);
			$details = Connection::getInstance()->getDetailedPlayerInfo($login);

			foreach($details as $key => $value)
			{
				if($value)
				{
					$param = lcfirst($key);
					$playerInfos->$param = $value;
				}
			}

			if($isSpectator)
				$this->spectators[$login] = $playerInfos;
			else
				$this->players[$login] = $playerInfos;
		}

		// if player can not be added to array, then we stop the onPlayerConnect event!
		catch(\Exception $e)
		{
			if($e->getCode() == -1000 && $e->getMessage() == 'Login unknown.')
				throw new SilentCriticalEventException($e->getMessage());
			else
				throw new CriticalEventException($e->getMessage());
		}
	}

	function onPlayerDisconnect($login)
	{
		$this->disconnectedPlayers[] = $login;

		if(isset($this->players[$login]))
			$this->players[$login]->isConnected = false;
		else if(isset($this->spectators[$login]))
			$this->spectators[$login]->isConnected = false;

		foreach($this->ranking as $key => $player)
			if($player->login == $login)
				unset($this->ranking[$key]);
	}

	function onPlayerChat($playerUid, $login, $text, $isRegistredCmd) {}
	function onPlayerManialinkPageAnswer($playerUid, $login, $answer, array $entries) {}
	function onEcho($internal, $public) {}
	function onServerStart() {}
	function onServerStop() {}
	function onBeginMatch($map) {}

	function onEndMatch($rankings, $map)
	{
		if($this->isWarmUp && $this->gameInfos->gameMode == GameInfos::GAMEMODE_LAPS)
		{
			$this->resetScores();
			$this->isWarmUp = false;
		}
		else
		{
			$rankings = Player::fromArrayOfArray($rankings);
			$this->updateRanking($rankings);
		}
	}

	function onBeginMap($map, $warmUp, $matchContinuation)
	{
		$this->checkpoints = array();

		$oldMap = $this->currentMap;
		$this->currentMap = Map::fromArray($map);
		Console::printlnFormatted('Map change: ' . TMStrings::stripAllTmStyle($oldMap->name) . ' -> ' . TMStrings::stripAllTmStyle($this->currentMap->name));

		$this->resetScores();

		if($warmUp)
			$this->isWarmUp = true;

		$gameInfos = Connection::getInstance()->getCurrentGameInfo();
		if($gameInfos != $this->gameInfos)
			foreach($gameInfos as $key => $value)
				$this->gameInfos->$key = $value;

		$serverOptions = Connection::getInstance()->getServerOptions();
		if($serverOptions != $this->server)
			foreach($serverOptions as $key => $value)
				$this->server->$key = $value;
	}

	function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
	{
		if(!$wasWarmUp)
		{
			$rankings = Player::fromArrayOfArray($rankings);
			$this->updateRanking($rankings);
		}
		else
		{
			$this->resetScores();
			$this->isWarmUp = false;
		}
	}

	function onBeginRound() {}

	function onEndRound()
	{
		// TODO find a better way to handle the -1000 "no race in progress" error ...
		try
		{
			if(count($this->players) || count($this->spectators))
			{
				$rankings = Connection::getInstance()->getCurrentRanking(-1, 0);
				$this->updateRanking($rankings);
			}
		}
		catch (\Exception $ex) {}
	}

	function onStatusChanged($statusCode, $statusName)
	{
		$this->serverStatus->code = $statusCode;
		$this->serverStatus->name = $statusName;
	}

	function getLapCheckpoints($player)
	{
		$login = $player->login;
		if(isset($this->checkpoints[$login]))
		{
			$checkCount = count($this->checkpoints[$login]) - 1;
			$offset = ($checkCount % $this->currentMap->nbCheckpoints) + 1;
			$checks = array_slice($this->checkpoints[$login], -$offset);

			if($checkCount >= $this->currentMap->nbCheckpoints)
			{
				$timeOffset = $this->checkpoints[$login][$checkCount - $offset];

				for ($i = 0; $i < count($checks); ++$i)
					$checks[$i] -= $timeOffset;
			}

			return $checks;
		}
		else
			return array();
	}

	function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex)
	{
		// reset all checkpoints on first checkpoint
		if ($checkpointIndex == 0)
			$this->checkpoints[$login] = array();
		// sanity check
		else if ($checkpointIndex > 0 &&
				(!isset($this->checkpoints[$login])
				|| !isset($this->checkpoints[$login][$checkpointIndex - 1])
				|| $timeOrScore < $this->checkpoints[$login][$checkpointIndex - 1]))
			return;

		// store current checkpoint score in array
		$this->checkpoints[$login][$checkpointIndex] = $timeOrScore;

		//print_r($this->checkpoints[$login]);
		// if player has finished a complete lap
		if($this->currentMap->nbCheckpoints && ($checkpointIndex + 1) % $this->currentMap->nbCheckpoints == 0)
		{
			$player = $this->getPlayerObject($login);
			if($player)
			{
				// get the checkpoints for current lap
				$checkpoints = array_slice($this->checkpoints[$login], -$this->currentMap->nbCheckpoints);

				// if we're at least in second lap we need to strip times from previous laps
				if($checkpointIndex >= $this->currentMap->nbCheckpoints)
				{
					// calculate checkpoint scores for current lap
					$offset = $this->checkpoints[$login][($checkpointIndex - $this->currentMap->nbCheckpoints)];
					for ($i = 0; $i < count($checkpoints); $i++)
						$checkpoints[$i] -= $offset;

					// calculate current lap score
					$timeOrScore -= $offset;
				}

				// last checkpoint has to be equal to finish time
				if(end($checkpoints) != $timeOrScore)
					return;

				// finally we tell everyone of the new lap time
				Dispatcher::dispatch(new Event(Event::ON_PLAYER_FINISH_LAP, $player, end($checkpoints), $checkpoints, $curLap));
			}
		}
	}

	function onPlayerFinish($playerUid, $login, $timeOrScore)
	{
		if(!isset($this->players[$login]))
			return;
		
		$player = $this->players[$login];

		switch($this->gameInfos->gameMode)
		{
			// check stunts
			case GameInfos::GAMEMODE_STUNTS:
				if($timeOrScore > 0 && ($player->score <= 0 || $timeOrScore > $player->score))
				{
					$oldScore = $player->score;
					$player->score = $timeOrScore;

					$rankings = Connection::getInstance()->getCurrentRanking(-1, 0);
					$this->updateRanking($rankings);

					if($player->score == $timeOrScore)
					{
						// sanity checks
						if(count($player->bestCheckpoints) != $this->currentMap->nbCheckpoints)
						{
							Console::println('Best score\'s checkpoint count does not match and was ignored!');
							Console::printPlayerScore($player);
							$player->score = $oldScore;
							return;
						}
						break;

						Dispatcher::dispatch(new Event(Event::ON_PLAYER_NEW_BEST_SCORE, $player, $oldScore, $timeOrScore));
					}
				}
				break;

			// check all other game modes
			default:
				if($timeOrScore > 0 && ($player->bestTime <= 0 || $timeOrScore < $player->bestTime))
				{
					$oldBest = $player->bestTime;
					$player->bestTime = $timeOrScore;
					if($this->gameInfos->gameMode !== GameInfos::GAMEMODE_TIMEATTACK)
					{
						$ranking = Connection::getInstance()->getCurrentRankingForLogin($player);
						$rankOld = $player->rank;
						$player->rank = $ranking[0]->rank;
						$player->bestTime = $ranking[0]->bestTime;
						$player->bestCheckpoints = $ranking[0]->bestCheckpoints;

						if($rankOld != $player->rank)
							Dispatcher::dispatch(new Event(Event::ON_PLAYER_NEW_RANK, $player, $rankOld, $player->rank));
					}
					else
						$this->updateRanking(Connection::getInstance()->getCurrentRanking(-1, 0));

					if($player->bestTime == $timeOrScore)
					{
						// sanity checks
						$totalChecks = 0;
						switch ($this->gameInfos->gameMode) {
							case GameInfos::GAMEMODE_LAPS:
								$totalChecks = $this->currentMap->nbCheckpoints * $this->gameInfos->lapsNbLaps;
								break;
							case GameInfos::GAMEMODE_TEAM:
							case GameInfos::GAMEMODE_ROUNDS:
							case GameInfos::GAMEMODE_CUP:
								if($this->currentMap->nbLaps > 0)
									$totalChecks = $this->currentMap->nbCheckpoints * ($this->gameInfos->roundsForcedLaps ?: $this->currentMap->nbLaps);
								else
									$totalChecks = $this->currentMap->nbCheckpoints;
								break;
							default:
								$totalChecks = $this->currentMap->nbCheckpoints;
								break;
						}

						if(count($player->bestCheckpoints) != $totalChecks) {
							Console::println('Best time\'s checkpoint count does not match and was ignored!');
							Console::printPlayerBest($player);
							$player->bestTime = $oldBest;
							return;
						}

						Dispatcher::dispatch(new Event(Event::ON_PLAYER_NEW_BEST_TIME, $player, $oldBest, $timeOrScore));
					}
				}
				break;
		}
	}

	function onPlayerIncoherence($playerUid, $login) {}
	function onBillUpdated($billId, $state, $stateName, $transactionId) {}
	function onTunnelDataReceived($playerUid, $login, $data) {}

	function onMapListModified($curMapIndex, $nextMapIndex, $isListModified)
	{
		if($isListModified)
		{
			$maps = Connection::getInstance()->getMapList(-1, 0);

			foreach($maps as $key => $map)
			{
				$storageKey = array_search($map, $this->maps);
				if($storageKey !== false)
					$maps[$key] = $this->maps[$storageKey];
				else
					$this->maps[$storageKey] = null;
			}
			$this->maps = $maps;
		}
		$this->nextMap = isset($this->maps[$nextMapIndex]) ? $this->maps[$nextMapIndex] : null;
	}

	function onPlayerInfoChanged($playerInfo)
	{
		$keys = array_keys($playerInfo);
		$keys = array_map('lcfirst', $keys);
		$keys[] = 'forceSpectator';
		$keys[] = 'isReferee';
		$keys[] = 'isPodiumReady';
		$keys[] = 'isUsingStereoscopy';
		$keys[] = 'spectator';
		$keys[] = 'temporarySpectator';
		$keys[] = 'pureSpectator';
		$keys[] = 'autoTarget';
		$keys[] = 'currentTargetId';

		$playerInfo = Player::fromArray($playerInfo);

		if($playerInfo->spectator == 0)
		{
			if(isset($this->players[$playerInfo->login]))
				foreach($keys as $key)
					$this->players[$playerInfo->login]->$key = $playerInfo->$key;
			else if(isset($this->spectators[$playerInfo->login]))
			{
				$this->players[$playerInfo->login] = $this->spectators[$playerInfo->login];
				unset($this->spectators[$playerInfo->login]);
				foreach($keys as $key)
					$this->players[$playerInfo->login]->$key = $playerInfo->$key;
				
				Dispatcher::dispatch(new Event(Event::ON_PLAYER_CHANGE_SIDE, $this->players[$playerInfo->login], 'spectator'));
			}
		}
		else
		{
			if(isset($this->spectators[$playerInfo->login]))
				foreach($keys as $key)
					$this->spectators[$playerInfo->login]->$key = $playerInfo->$key;
			else if(isset($this->players[$playerInfo->login]))
			{
				$this->spectators[$playerInfo->login] = $this->players[$playerInfo->login];
				unset($this->players[$playerInfo->login]);
				foreach ($keys as $key)
					$this->spectators[$playerInfo->login]->$key = $playerInfo->$key;
				
				Dispatcher::dispatch(new Event(Event::ON_PLAYER_CHANGE_SIDE, $this->spectators[$playerInfo->login], 'player'));
			}
		}
	}

	function onManualFlowControlTransition($transition) {}

	function onVoteUpdated($stateName, $login, $cmdName, $cmdParam) {
		if(!($this->currentVote instanceof Vote))
			$this->currentVote = new Vote();
		$this->currentVote->status = $stateName;
		$this->currentVote->callerLogin = $login;
		$this->currentVote->cmdName = $cmdName;
		$this->currentVote->cmdParam = $cmdParam;
	}

	function onRulesScriptCallback($param1, $param2) {}

	#endRegion

	/**
	 * Give a Player Object for the corresponding login
	 * @param string $login
	 * @return \ManiaLive\DedicatedApi\Structures\Player
	 */
	function getPlayerObject($login)
	{
		if(isset($this->players[$login]))
			return $this->players[$login];
		else if(isset($this->spectators[$login]))
			return $this->spectators[$login];
		else
			return null;
	}

	protected function updateRanking($rankings)
	{
		$changed = array();
		foreach($rankings as $ranking)
		{
			if($ranking->rank == 0)
				continue;
			else if(isset($this->players[$ranking->login]))
			{
				$player = $this->players[$ranking->login];
				$rankOld = $player->rank;

				$player->playerId = $ranking->playerId;
				$player->rank = $ranking->rank;
				$player->bestTime = $ranking->bestTime;
				$player->bestCheckpoints = $ranking->bestCheckpoints;
				$player->score = $ranking->score;
				$player->nbrLapsFinished = $ranking->nbrLapsFinished;
				$player->ladderScore = $ranking->ladderScore;

				if($rankOld != $player->rank)
					Dispatcher::dispatch(new Event(Event::ON_PLAYER_NEW_RANK, $player, $rankOld, $player->rank));

				$this->ranking[$ranking->rank] = $this->players[$ranking->login];
			}
			else if(isset($this->spectators[$ranking->login]))
			{
				$spectator = $this->spectators[$ranking->login];

				$spectator->playerId = $ranking->playerId;
				$spectator->rank = $ranking->rank;
				$spectator->bestTime = $ranking->bestTime;
				$spectator->bestCheckpoints = $ranking->bestCheckpoints;
				$spectator->score = $ranking->score;
				$spectator->nbrLapsFinished = $ranking->nbrLapsFinished;
				$spectator->ladderScore = $ranking->ladderScore;

				$this->ranking[$ranking->rank] = $this->spectators[$ranking->login];
			}
		}
	}

	protected function resetScores()
	{
		foreach($this->players as $key => $player)
		{
			$player->bestTime = 0;
			$player->rank = 0;
			$player->point = 0;
		}

		foreach($this->spectators as $spectator)
		{
			$spectator->bestTime = 0;
			$spectator->rank = 0;
			$spectator->point = 0;
		}
	}
}

?>
