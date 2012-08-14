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

use ManiaLib\Utils\Formatting;
use ManiaLive\Event\Dispatcher;
use ManiaLive\Application\Listener as AppListener;
use ManiaLive\Application\Event as AppEvent;
use ManiaLive\DedicatedApi\Callback\Listener as ServerListener;
use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;
use DedicatedApi\Connection;
use DedicatedApi\Structures\GameInfos;
use DedicatedApi\Structures\Map;
use DedicatedApi\Structures\Player;
use DedicatedApi\Structures\Vote;
use ManiaLive\Utilities\Console;
use ManiaLive\Application\SilentCriticalEventException;
use ManiaLive\Application\CriticalEventException;

/**
 * Contain every important data about the server
 */
class Storage extends \ManiaLib\Utils\Singleton implements ServerListener, AppListener
{

	/**
	 *
	 * @var \DedicatedApi\Connection
	 */
	private $connection;
	private $disconnectedPlayers = array();

	/**
	 * Player's checkpoints
	 */
	private $checkpoints = array();

	/**
	 * Contains Player object. It represents the player connected to the server
	 * @var \DedicatedApi\Structures\Player[]
	 */
	public $players = array();

	/**
	 * Contains Player object. It represents the spectators connected to the server
	 * @var \DedicatedApi\Structures\Player[]
	 */
	public $spectators = array();

	/**
	 * Contains Map objects. It represents the current maps available on the server
	 * @var \DedicatedApi\Structures\Map[]
	 */
	public $maps;

	/**
	 * Represents the current Map object
	 * @var \DedicatedApi\Structures\Map
	 */
	public $currentMap;

	/**
	 * Represents the next Map object
	 * @var \DedicatedApi\Structures\Map
	 */
	public $nextMap;

	/**
	 * Represents the Current Server Options
	 * @var \DedicatedApi\Structures\ServerOptions
	 */
	public $server;

	/**
	 * Represents the Current Game Infos
	 * @var \DedicatedApi\Structures\GameInfos
	 */
	public $gameInfos;

	/**
	 * Represents the current Server Status
	 * @var \DedicatedApi\Structures\Status
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
		$config = \ManiaLive\DedicatedApi\Config::getInstance();
		$this->connection = Connection::factory($config->host, $config->port, $config->timeout, $config->user, $config->password);
		$this->serverStatus = $this->connection->getStatus();

		$players = $this->connection->getPlayerList(-1, 0);
		foreach($players as $player)
		{
			try
			{
				$details = $this->connection->getDetailedPlayerInfo($player->login);

				foreach($details as $key => $value)
					if($value) $player->$key = $value;

				if($player->spectatorStatus % 10 == 0) $this->players[$player->login] = $player;
				else $this->spectators[$player->login] = $player;
			}
			catch(\Exception $e)
			{
				
			}
		}

		try
		{
			$this->maps = $this->connection->getMapList(-1, 0);
			$this->nextMap = $this->maps[$this->connection->getNextMapIndex()];
			$this->currentMap = $this->connection->getCurrentMapInfo();
			Console::printlnFormatted('Current map: '.Formatting::stripStyles($this->currentMap->name));
		}
		catch(\Exception $e)
		{
			$this->maps = array();
			$this->nextMap = null;
			$this->currentMap = null;
		}
		$this->server = $this->connection->getServerOptions();
		$this->gameInfos = $this->connection->getCurrentGameInfo();
		try
		{
			$this->serverLogin = $this->connection->getSystemInfo()->serverLogin;
		}
		catch(\Exception $e)
		{
			$this->serverLogin = null;
		}
	}

	function onPostLoop()
	{
		foreach($this->disconnectedPlayers as $login)
		{
			if(isset($this->spectators[$login]) && !$this->spectators[$login]->isConnected) unset($this->spectators[$login]);
			else if(isset($this->players[$login]) && !$this->players[$login]->isConnected) unset($this->players[$login]);
		}
		$this->disconnectedPlayers = array();

		if($this->currentVote instanceof Vote && $this->currentVote->status != Vote::STATE_NEW) $this->currentVote = null;
	}

	function onRun()
	{
		
	}

	function onPreLoop()
	{
		
	}

	function onTerminate()
	{
		
	}

	#endRegion
	#region Implementation of DedicatedApi\Listener

	function onPlayerConnect($login, $isSpectator)
	{
		try
		{
			$playerInfos = $this->connection->getPlayerInfo($login, 1);
			$details = $this->connection->getDetailedPlayerInfo($login);

			foreach($details as $key => $value)
				if($value) $playerInfos->$key = $value;

			if($isSpectator) $this->spectators[$login] = $playerInfos;
			else $this->players[$login] = $playerInfos;
		}

		// if player can not be added to array, then we stop the onPlayerConnect event!
		catch(\Exception $e)
		{
			if($e->getCode() == -1000 && $e->getMessage() == 'Login unknown.')
					throw new SilentCriticalEventException($e->getMessage());
			else throw new CriticalEventException($e->getMessage());
		}
	}

	function onPlayerDisconnect($login)
	{
		$this->disconnectedPlayers[] = $login;

		if(isset($this->players[$login])) $this->players[$login]->isConnected = false;
		else if(isset($this->spectators[$login])) $this->spectators[$login]->isConnected = false;
	}

	function onPlayerChat($playerUid, $login, $text, $isRegistredCmd)
	{
		
	}

	function onPlayerManialinkPageAnswer($playerUid, $login, $answer, array $entries)
	{
		
	}

	function onEcho($internal, $public)
	{
		
	}

	function onServerStart()
	{
		try
		{
			$this->serverLogin = $this->connection->getMainServerPlayerInfo()->login;
			$this->maps = $this->connection->getMapList(-1, 0);
		}
		catch(\Exception $e)
		{
			$this->serverLogin = null;
			$this->maps = array();
		}
	}

	function onServerStop()
	{
		
	}

	function onBeginMatch()
	{
		
	}

	function onEndMatch($rankings)
	{
		if($this->isWarmUp && $this->gameInfos->gameMode == GameInfos::GAMEMODE_LAPS)
		{
			$this->resetScores();
			$this->isWarmUp = false;
		}
		else $this->updateRanking(Player::fromArrayOfArray($rankings));
	}

	function onBeginMap($map, $warmUp, $matchContinuation)
	{
		$this->checkpoints = array();

		$oldMap = $this->currentMap;
		$this->currentMap = Map::fromArray($map);
		if($oldMap)
		{
			Console::printlnFormatted('Map change: '.Formatting::stripStyles($oldMap->name).' -> '.Formatting::stripStyles($this->currentMap->name));
		}
		$this->resetScores();

		if($warmUp) $this->isWarmUp = true;

		$gameInfos = $this->connection->getCurrentGameInfo();
		if($gameInfos != $this->gameInfos) foreach($gameInfos as $key => $value)
				$this->gameInfos->$key = $value;

		$serverOptions = $this->connection->getServerOptions();
		if($serverOptions != $this->server) foreach($serverOptions as $key => $value)
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

	function onBeginRound()
	{
		
	}

	function onEndRound()
	{
		// TODO find a better way to handle the -1000 "no race in progress" error ...
		try
		{
			if(count($this->players) || count($this->spectators))
					$this->updateRanking($this->connection->getCurrentRanking(-1, 0));
		}
		catch(\Exception $ex)
		{
			
		}
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

				for($i = 0; $i < count($checks); ++$i)
					$checks[$i] -= $timeOffset;
			}

			return $checks;
		}
		else return array();
	}

	function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex)
	{
		// reset all checkpoints on first checkpoint
		if($checkpointIndex == 0) $this->checkpoints[$login] = array();
		// sanity check
		else if($checkpointIndex > 0 &&
			(!isset($this->checkpoints[$login])
			|| !isset($this->checkpoints[$login][$checkpointIndex - 1])
			|| $timeOrScore < $this->checkpoints[$login][$checkpointIndex - 1])) return;

		// store current checkpoint score in array
		$this->checkpoints[$login][$checkpointIndex] = $timeOrScore;

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
					for($i = 0; $i < count($checkpoints); ++$i)
						$checkpoints[$i] -= $offset;

					// calculate current lap score
					$timeOrScore -= $offset;
				}

				// last checkpoint has to be equal to finish time
				if(end($checkpoints) != $timeOrScore) return;

				// finally we tell everyone of the new lap time
				Dispatcher::dispatch(new Event(Event::ON_PLAYER_FINISH_LAP, $player, end($checkpoints), $checkpoints, $curLap));
			}
		}
	}

	function onPlayerFinish($playerUid, $login, $timeOrScore)
	{
		if(!isset($this->players[$login])) return;
		$player = $this->players[$login];

		switch($this->gameInfos->gameMode)
		{
			// check stunts
			case GameInfos::GAMEMODE_STUNTS:
				if($timeOrScore > 0 && ($player->score <= 0 || $timeOrScore > $player->score))
				{
					$oldScore = $player->score;
					$this->updateRanking($this->connection->getCurrentRanking(-1, 0));

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

						Dispatcher::dispatch(new Event(Event::ON_PLAYER_NEW_BEST_SCORE, $player, $oldScore, $timeOrScore));
					}
				}
				break;

			// check all other game modes
			default:
				if($timeOrScore > 0 && ($player->bestTime <= 0 || $timeOrScore < $player->bestTime))
				{
					$oldBest = $player->bestTime;
					$this->updateRanking($this->connection->getCurrentRanking(-1, 0));

					if($player->bestTime == $timeOrScore)
					{
						// sanity checks
						$totalChecks = 0;
						switch($this->gameInfos->gameMode)
						{
							case GameInfos::GAMEMODE_LAPS:
								$totalChecks = $this->currentMap->nbCheckpoints * $this->gameInfos->lapsNbLaps;
								break;
							case GameInfos::GAMEMODE_TEAM:
							case GameInfos::GAMEMODE_ROUNDS:
							case GameInfos::GAMEMODE_CUP:
								if($this->currentMap->nbLaps > 0)
										$totalChecks = $this->currentMap->nbCheckpoints * ($this->gameInfos->roundsForcedLaps ? : $this->currentMap->nbLaps);
								else $totalChecks = $this->currentMap->nbCheckpoints;
								break;
							default:
								$totalChecks = $this->currentMap->nbCheckpoints;
								break;
						}

						if(count($player->bestCheckpoints) != $totalChecks)
						{
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

	function onPlayerIncoherence($playerUid, $login)
	{
		
	}

	function onBillUpdated($billId, $state, $stateName, $transactionId)
	{
		
	}

	function onTunnelDataReceived($playerUid, $login, $data)
	{
		
	}

	function onMapListModified($curMapIndex, $nextMapIndex, $isListModified)
	{
		if($isListModified)
		{
			$maps = $this->connection->getMapList(-1, 0);

			foreach($maps as $key => $map)
			{
				$storageKey = array_search($map, $this->maps);
				if($storageKey !== false) $maps[$key] = $this->maps[$storageKey];
				else $this->maps[$storageKey] = null;
			}
			$this->maps = $maps;
		}
		$this->nextMap = isset($this->maps[$nextMapIndex]) ? $this->maps[$nextMapIndex] : null;
	}

	function onPlayerInfoChanged($playerInfo)
	{
		$playerInfo = Player::fromArray($playerInfo);
		$player = $this->getPlayerObject($playerInfo->login);
		if(!$player) return;

		$wasSpectator = $player->spectator;
		foreach($playerInfo as $key => $value)
			$player->$key = $value;

		if($wasSpectator && !$player->spectator)
		{
			unset($this->spectators[$player->login]);
			$this->players[$player->login] = $player;
			Dispatcher::dispatch(new Event(Event::ON_PLAYER_CHANGE_SIDE, $player, 'spectator'));
		}
		else if(!$wasSpectator && $player->spectator)
		{
			unset($this->players[$player->login]);
			$this->spectators[$player->login] = $player;
			Dispatcher::dispatch(new Event(Event::ON_PLAYER_CHANGE_SIDE, $player, 'player'));
		}
	}

	function onManualFlowControlTransition($transition)
	{
		
	}

	function onVoteUpdated($stateName, $login, $cmdName, $cmdParam)
	{
		if(!($this->currentVote instanceof Vote)) $this->currentVote = new Vote();
		$this->currentVote->status = $stateName;
		$this->currentVote->callerLogin = $login;
		$this->currentVote->cmdName = $cmdName;
		$this->currentVote->cmdParam = $cmdParam;
	}

	function onModeScriptCallback($param1, $param2)
	{
		
	}

	#endRegion

	/**
	 * Give a Player Object for the corresponding login
	 * @param string $login
	 * @return \ManiaLive\DedicatedApi\Structures\Player
	 */
	function getPlayerObject($login)
	{
		if(isset($this->players[$login])) return $this->players[$login];
		else if(isset($this->spectators[$login])) return $this->spectators[$login];
		else return null;
	}

	protected function updateRanking($rankings)
	{
		foreach($rankings as $ranking)
		{
			if($ranking->rank == 0) continue;

			$player = $this->getPlayerObject($ranking->login);
			if(!$player) continue;

			$rankOld = $player->rank;
			$player->rank = $ranking->rank;
			$player->bestTime = $ranking->bestTime;
			$player->bestCheckpoints = $ranking->bestCheckpoints;
			$player->score = $ranking->score;
			$player->nbrLapsFinished = $ranking->nbrLapsFinished;
			$player->ladderScore = $ranking->ladderScore;

			if(!$player->isSpectator && $rankOld != $player->rank)
					Dispatcher::dispatch(new Event(Event::ON_PLAYER_NEW_RANK, $player, $rankOld, $player->rank));
		}
	}

	protected function resetScores()
	{
		foreach($this->players as $player)
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
