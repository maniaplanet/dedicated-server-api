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

namespace ManiaLivePlugins\Standard\Dedimania;

use ManiaLive\DedicatedApi\Structures\GameInfos;
use ManiaLive\DedicatedApi\Structures\Player;
use ManiaLivePlugins\Standard\Version;
use ManiaLive\Gui\Windowing\WindowHandler;
use ManiaLive\Config\Loader;
use ManiaLivePlugins\Standard\Menubar\Gui\Windows\Menu;
use ManiaLive\PluginHandler\Dependency;
use ManiaLivePlugins\Standard\Dedimania\Gui\Windows\CheckpointTime;
use ManiaLivePlugins\Standard\Dedimania\Runnables\NotificationCall;
use ManiaLive\Utilities\Time;
use ManiaHome\ManiaHomeClient;
use ManiaHome;
use ManiaLivePlugins\Standard\Dedimania\Gui\Windows\Leaderboard;
use ManiaLive\Gui\Windowing\Windows\Info;
use ManiaLive\Utilities\String;
use ManiaLib\Gui\Elements\Icons128x128_1;
use ManiaLive\Gui\Handler\GuiHandler;
use ManiaLivePlugins\Standard\Dedimania\Structures\Record;
use ManiaLivePlugins\Standard\Dedimania\Runnables\DedimaniaCall;
use ManiaLivePlugins\Standard\Dedimania\Structures\Challenge;
use ManiaLive\Event\Dispatcher;
use ManiaLive\Data\Storage;
use ManiaLive\Utilities\Console;
use ManiaLive\DedicatedApi\Connection;
use ManiaLive\Threading\Commands\RunCommand;
use ManiaLive\Threading\ThreadPool;
use ManiaLive\DedicatedApi\Xmlrpc\Message;

class Dedimania extends \ManiaLive\PluginHandler\Plugin implements
	\ManiaLive\Data\Listener,
	\ManiaLive\Features\Tick\Listener,
	\ManiaLive\Threading\Listener
{
	protected $storage;
	protected $last_players_update;

	protected $packmask;
	protected $version_info;
	protected $version;

	protected $records_max;
	protected $records;
	protected $records_last;
	protected $records_new;
	protected $records_by_login;
	protected $records_count;

	/**
	 * @var \ManiaLivePlugins\Standard\Dedimania\Structures\Challenge
	 */
	protected $challenge_previous;
	/**
	 * @var \ManiaLivePlugins\Standard\Dedimania\Structures\Challenge
	 */
	protected $challenge_current;

	protected $ready;
	protected $callback_queue;

	/**
	 * Configuration
	 * @var string
	 */
	public static $password = null;
	public static $idleTimeout = 200;
	public static $notifications = true;
	public static $notifyNewFirstRecord = '%player% drove new first Dedimania record with a time of %time%!';
	public static $notifyNewRecord = '%player% just ranked %rank% on Dedimania with a time of %time%!';
	public static $notifyImprovedFirstRecord = '%player% beat his own first Dedimania record with a time of %time%!';
	public static $notifyImprovedRecord = '%player% moved on the %rank% Dedimania rank by finishing with a time of %time%!';
	public static $notifyImprovedRecordTimeOnly = '%player% secured his %rank% Dedimania rank by driving a time of %time%!';

	const RECORDS_MAX = 30;

	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/PluginHandler/ManiaLive\PluginHandler.Plugin::onInit()
	 */
	public function onInit()
	{
		$this->setVersion(1.2);
		$this->setPublicMethod('showRecordWindow');

		// link plugin to repository to check for updates
		$this->setRepositoryId(11);
		$this->setRepositoryVersion(2622);

		// manialive version check
		$dependency = new Dependency('ManiaLive', 194);
		$this->addDependency($dependency);
	}

	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/PluginHandler/ManiaLive\PluginHandler.Plugin::onLoad()
	 */
	public function onLoad()
	{
		$this->setBusy();
		$this->records = array();
		$this->records_new = array();
		$this->records_by_login = array();
		$this->callback_queue = array();

		// get storage reference ...
		$this->storage = Storage::getInstance();

		// gather some information ...
		$this->packmask = Connection::getInstance()->getServerPackMask();
		$this->version_info = Connection::getInstance()->getVersion();
		$this->version = Utilities::parseGame($this->version_info->name);

		$this->enableThreadingEvents();
		$this->enableDedicatedEvents();
		$this->enableTickerEvent();
		$this->enableStorageEvents();
		$this->enablePluginEvents();

		// not yet ...
		$this->last_players_update = time() + self::$idleTimeout;

		// start worker thread ...
		$this->createThread();

		// register chat command for displaying records ...
		$cmd = $this->registerChatCommand('dedimania', 'showRecordWindow', 0, true);
		$cmd->help = 'displays window with dedimania rankings for the current challenge.';

		// check if password has been set in the config file
		if (self::$password == null)
		{
			throw new ConfigurationException('You need to set the Standard\Dedimania.password option in the config.ini!');
		}

		$this->buildMenu();

		// connect for the first time ...
		$this->connectToDedimania();
	}

	/**
	 * Build Dedimania Menu.
	 */
	protected function buildMenu()
	{
		if ($this->isPluginLoaded('Standard\Menubar', 1.1))
		{
			// set menu icon for dedimanias menu ...
			$this->callPublicMethod('Standard\Menubar',
				'initMenu',
				Icons128x128_1::Replay);

			// add button for records window ...
			$this->callPublicMethod('Standard\Menubar',
				'addButton',
				'Show Records',
				array($this, 'showRecordWindow'),
				false);
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/PluginHandler/ManiaLive\PluginHandler.Plugin::onPluginLoaded()
	 */
	function onPluginLoaded($pluginId)
	{
		if ($pluginId == 'Standard\Menubar')
		{
			$this->buildMenu();
		}
	}

	/**
	 * If the Process dies, then also our persistent
	 * connection will brake, this means reconnect!
	 * @param Thread $thread
	 */
	public function onThreadRestart($thread)
	{
		if ($thread->getId() == $this->getThreadId())
		{
			// begin connection to dedimania ...
			$this->connectToDedimania();
		}
	}

	/**
	 * Establishes a connection to
	 * the dedimania network for the current
	 * Process.
	 */
	public function connectToDedimania()
	{
		$this->writeConsole('Starting authentication on server ...');

		$call = new DedimaniaCall();

		$call->addRequest(new Request('dedimania.GetVersion', array()));

		// try to open session at auth server ...
		$params = array
		(
			'Game' => $this->version,
			'Login' => $this->storage->serverLogin,
			'Password' => self::$password,
			'Packmask' => $this->packmask,
			'ServerBuild' => $this->version_info->build,
			'ServerVersion' => $this->version_info->version,
			'Tool' => 'ManiaLive',
			'Version' => \ManiaLiveApplication\Version
		);
		$call->addRequest(new Request('dedimania.Authenticate', array((object)$params)));

		// validate account to be sure ...
		$call->addRequest(new Request('dedimania.ValidateAccount', array()));

		// send request ...
		$this->writeConsole('Please wait, opening session ...');

		$this->sendWorkToOwnThread($call, 'cbConnectToDedimania');
	}

	/**
	 * This shows the current rankingboard
	 * to the player who entered the chatcommand.
	 * @param $login
	 */
	public function showRecordWindow($login, $info = null)
	{
		if ($info && $info->isShown())
			$info->hide();

		if (!$this->isReady())
		{
			// show info message ...
			$info = Info::Create($login);
			$info->setSize(50, 12);
			$info->setTitle('Please Stand By');
			$info->setText("Dedimania is loading records, this may take a few seconds ...\nThe records will be displayed as soon as loading is finished!");
			$info->centerOnScreen();
			WindowHandler::showDialog($info);

			// register this function to be called back when records
			// have been loaded ...
			$callback = array($this, 'showRecordWindow');
			$this->registerWaitForReady($callback, $login, $info);

			return;
		}

		// create window instance for player ...
		$window = Leaderboard::Create($login);
		$window->setSize(80, 61);

		// prepare cols ...
		$window->addColumn('Rank', 0.1);
		$window->addColumn('Login', 0.3);
		$window->addColumn('NickName', 0.4);
		$window->addColumn('Time', 0.2);

		// refresh records for this window ...
		$window->clearRecords();
		foreach ($this->records as $record)
		{
			$entry = array
			(
				'Rank' => $record->rank,
				'Login' => $record->login,
				'NickName' => $record->nickName,
				'Time' => Time::fromTM($record->best)
			);
			$window->addRecord($entry);
		}

		// display or update window ...
		$window->centerOnScreen();
		$window->show();
	}

	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $command
	 */
	public function cbConnectToDedimania($command)
	{
		$err = false;

		// okay process got server's response!
		$this->writeConsole('Done!');

		if ($command->result[0]['OK'])
		{
			$this->writeConsole('Connected to Dedimania Version ' . $command->result[0]['Version'] . '!');
			$this->records_max = $command->result[0]['MaxRecords'];
		}

		// we check the authentication query for success ..
		if ($command->result[1]['OK'])
		{
			$this->writeConsole('Successfully authenticated!');
		}
		else
		{
			$err = true;
		}

		// we check the authentication query for success ..
		if ($command->result[2]['OK'])
		{
			$this->writeConsole('Account is valid!');
		}
		else
		{
			if ($command->result[2]['Error'])
			{
				$this->writeConsole($command->result[2]['Error']['Message']);
			}
			$err = true;
		}

		if ($err)
		{
			die("Error: Could not connect to Dedimania, authentication failed!\n");
		}

		// insert currently running challenge ...
		$this->onBeginRace($this->storage->currentChallenge);
	}

	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Features/Tick/ManiaLive\Features\Tick.Listener::onTick()
	 */
	public function onTick()
	{
		if (time() > $this->last_players_update)
		{
			$call = new DedimaniaCall();

			$params = array(
				$this->version,
				$this->storage->gameInfos->gameMode,
				(object)$this->getServerInfoArray(),
				(object)$this->getPlayersArray()
			);

			$call->addRequest(new Request('dedimania.UpdateServerPlayers', $params));

			$this->sendWorkToOwnThread($call, 'cbTick');

			$this->last_players_update = time() + self::$idleTimeout;
		}
	}

	/**
	 *
	 * Enter description here ...
	 * @param $command
	 */
	public function cbTick($command)
	{
		if ($command->result[0]['OK'])
		{
			$this->writeConsole('Successfully sent keep-alive to server!');
		}
		else
		{
			$this->writeConsole('ERROR: Could not send keep-alive!');
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/DedicatedApi/Callback/ManiaLive\DedicatedApi\Callback.Adapter::onPlayerConnect()
	 */
	public function onPlayerConnect($login, $isSpectator)
	{
		$player = $this->storage->getPlayerObject($login);

		$call = new DedimaniaCall();

		$params = array
		(
			$this->version,
			$player->login,
			$player->nickName,
			$player->path,
			'unknown',
			intval($player->ladderStats['PlayerRankings'][0]['Ranking']),
			($player->isSpectator != null && $player->isSpectator != false),
			($player->isInOfficialMode != null && $player->isInOfficialMode != false)
		);

		$call->addRequest(new Request('dedimania.PlayerArrive', $params));

		$this->sendWorkToOwnThread($call, 'cbPlayerConnect');
	}

	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $command
	 */
	public function cbPlayerConnect($command)
	{
		if ($command->result[0]['OK'])
		{
			$this->writeConsole('Player ' . $command->result[0]['Login'] . ' has been marked online!');
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/DedicatedApi/Callback/ManiaLive\DedicatedApi\Callback.Adapter::onPlayerDisconnect()
	 */
	public function onPlayerDisconnect($login)
	{
		$call = new DedimaniaCall();

		$params = array
		(
			$this->version,
			$login
		);

		$call->addRequest(new Request('dedimania.PlayerLeave', $params));

		$this->sendWorkToOwnThread($call, 'cbPlayerDisconnect');
	}

	/**
	 *
	 * Enter description here ...
	 * @param $command
	 */
	public function cbPlayerDisconnect($command)
	{
		if ($command->result[0]['OK'])
		{
			$this->writeConsole('Player ' . $command->result[0]['Login'] . ' has been marked offline!');
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/DedicatedApi/Callback/ManiaLive\DedicatedApi\Callback.Adapter::onBeginRace()
	 */
	public function onBeginRace($challenge)
	{
		// reset and busy ...
		$this->records = null;
		$this->records_last = null;
		$this->records_new = null;
		$this->setBusy();

		if ($challenge == null)
			return;
		elseif (is_array($challenge))
			$challenge = \ManiaLive\DedicatedApi\Structures\Challenge::fromArray($challenge);

		$this->last_players_update = time() + self::$idleTimeout;

		$call = new DedimaniaCall();

		$params = array
		(
			$challenge->uId,
			$challenge->name,
			$challenge->environnement,
			$challenge->author,
			$this->version,
			$this->storage->gameInfos->gameMode,
			(object)$this->getServerInfoArray(),
			$this->records_max,
			$this->getPlayersArray()
		);

		$call->addRequest(new Request('dedimania.CurrentChallenge', $params));

		$this->sendWorkToOwnThread($call, 'cbBeginChallenge');
	}

	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/DedicatedApi/Callback/ManiaLive\DedicatedApi\Callback.Adapter::onBeginChallenge()
	 */
	public function onBeginChallenge($challenge, $warmUp, $matchContinuation)
	{

	}

	/**
	 * {'Uid': string, 'TotalRaces': int, 'TotalPlayers': int, 'TimeAttackRaces': int, 'TimeAttackPlayers': int, 'NumberOfChecks': int, 'ServerMaxRecords': int, 'Records': array of struct {'Login': string, 'NickName': string, 'Best': int, 'Rank': int, 'Checks': array of int, 'Vote': int} }
	 * Enter description here ...
	 * @param unknown_type $command
	 */
	public function cbBeginChallenge($command)
	{
		if (!$command->result[0]['OK'])
			return;

		// parse challenge object from response ...
		$challenge = Challenge::fromArray($command->result[0]);

		// destroy old challenge and free records
		if ($this->challenge_previous != null)
		{
			$this->challenge_previous->destroy();
		}

		$this->challenge_previous = $this->challenge_current;
		$this->challenge_current = $challenge;

		// records to the current challenge ...
		$this->records = $challenge->records;

		// records organized by logins ...
		$this->records_by_login = array();
		foreach ($this->records as $record)
		{
			$this->records_by_login[$record->login] = $record;
		}

		// how many records are there in total ...
		$this->records_count = count($challenge->records);

		// set link to the last record on the leaderboard ...
		if ($this->records_count > 0)
			$this->records_last = $challenge->records[count($challenge->records)-1];
		else
			$this->records_last = null;

		// records that will be new inserted ...
		$this->records_new = array();

		// dedimania is ready, we can receive records!
		$this->setReady();

		// loaded records for that track!
		$this->writeConsole('Got records for: ' . $challenge->uid);
	}

	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/DedicatedApi/Callback/ManiaLive\DedicatedApi\Callback.Adapter::onEndRace()
	 */
	public function onEndRace($rankings, $challenge)
	{
		// this is not allowed
		if ($this->storage->gameInfos->roundsForcedLaps != 0)
		{
			Console::println('Dedimania does not accept records driven with RoundsForcedLaps unequal to 0');
			return;
		}

		// check if there are any records to send
		if (!is_array($this->records_new) || empty($this->records_new))
		{
			return;
		}

		$call = new DedimaniaCall();

		// build times array
		$times = array();
		foreach ($this->records_new as $record)
		{
			$times[] = array
			(
				'Login' => $record->login,
				'Best' => $record->best,
				'Checks' => $record->checks
			);
		}

		// Uid, Name, Environment, Author, Game, Mode, NumberOfChecks, MaxGetTimes, Times
		$params = array
		(
			$this->storage->currentChallenge->uId,
			$this->storage->currentChallenge->name,
			$this->storage->currentChallenge->environnement,
			$this->storage->currentChallenge->author,
			$this->version,
			$this->storage->gameInfos->gameMode,
			$this->storage->currentChallenge->nbCheckpoints,
			$this->records_max,
			$times
		);

		$call->addRequest(new Request('dedimania.ChallengeRaceTimes', $params));

		$this->sendWorkToOwnThread($call, 'cbEndRace');
	}

	/**
	 *
	 * Enter description here ...
	 * @param $command
	 */
	public function cbEndRace($command)
	{
		if (!$command->result[0]['OK'])
			return;

		$challenge = Challenge::fromArray($command->result[0]);

		$this->writeConsole('Inserted records for: ' . $challenge->uid);
	}

	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/DedicatedApi/Callback/ManiaLive\DedicatedApi\Callback.Adapter::onEndChallenge()
	 */
	public function onEndChallenge($rankings, $challenge, $wasWarmUp, $matchContinuesOnNextChallenge, $restartChallenge)
	{
		foreach ($this->storage->players as $login => $player)
		{
			$cpwin = CheckpointTime::Create($login);
			$cpwin->hide();
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/DedicatedApi/Callback/ManiaLive\DedicatedApi\Callback.Adapter::onPlayerCheckpoint()
	 */
	public function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex)
	{
		if ($this->storage->gameInfos->gameMode == GameInfos::GAMEMODE_LAPS)
		{
			$player = new Player;
			$player->login = $login;
			$checks = $this->storage->getLapCheckpoints($player);
			$timeOrScore = end($checks);
			$checkpointIndex = key($checks);

			if ((($checkpointIndex + 1) % $this->storage->currentChallenge->nbCheckpoints) == 0)
			{
				return;
			}
		}

		$this->displayCheckpointWidget($login, $timeOrScore, $checkpointIndex);
	}

	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $login
	 * @param unknown_type $timeOrScore
	 * @param unknown_type $checkpointIndex
	 */
	protected function displayCheckpointWidget($login, $timeOrScore, $checkpointIndex)
	{
		if ($this->records_last != null)
		{
			// look for the record that we are comparing to
			$compare = $this->records_last;
			if (isset($this->records_by_login[$login]))
			{
				// player is ranked as first, so we can only compare to his own time
				if ($this->records_by_login[$login]->rank == 1)
					$compare = $this->records_by_login[$login];

				// if ranked at least second, we can compare his time to a better one
				else
					$compare = $this->records[$this->records_by_login[$login]->rank - 2];
			}

			// check whether there is a time for the current checkpoint
			if (isset($compare->checks[$checkpointIndex]))
			{
				// calculate the difference
				$cp_best = $compare->checks[$checkpointIndex];
				$diff = $timeOrScore - $cp_best;

				// and display the window
				$cpwin = CheckpointTime::Create($login);

				if ($checkpointIndex == 0)
					$cpwin->clearTimes();

				$cpwin->setPosition(0, 43);
				$cpwin->setSize(20, 10);
				$cpwin->addTime($diff);
				$cpwin->show();
			}
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/DedicatedApi/Callback/ManiaLive\DedicatedApi\Callback.Adapter::onPlayerFinish()
	 */
	public function onPlayerFinish($playerUid, $login, $timeOrScore)
	{
		if ($timeOrScore == 0 && $login != $this->storage->serverLogin)
		{
			$cpwin = CheckpointTime::Create($login);
			$cpwin->clearTimes();
			$cpwin->show();
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/DedicatedApi/Callback/ManiaLive\DedicatedApi\Callback.Adapter::onBeginRound()
	 */
	public function onBeginRound()
	{
		foreach ($this->storage->players as $login => $player)
		{
			$cpwin = CheckpointTime::Create($login);
			$cpwin->clearTimes();
			$cpwin->show();
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/PluginHandler/ManiaLive\PluginHandler.Plugin::onPlayerFinishLap()
	 */
	public function onPlayerFinishLap($player, $timeOrScore, $checkpoints, $nbLap)
	{
		// only in laps mode!
		if ($this->storage->gameInfos->gameMode != GameInfos::GAMEMODE_LAPS)
		{
			return;
		}

		Console::printDebug($player->login . ' finished lap #' . $nbLap . '!');

		// create record object ...
		$record = new Record();
		$record->best = $timeOrScore;
		$record->checks = $checkpoints;
		$record->login = $player->login;
		$record->nickName = $player->nickName;
		$record->challenge = $this->challenge_current;

		// display widget
		if ($this->storage->gameInfos->gameMode == GameInfos::GAMEMODE_LAPS)
		{
			$checks = $this->storage->getLapCheckpoints($player);
			$timeOrScore = end($checks);
			$checkpointIndex = key($checks);

			$this->displayCheckpointWidget($player->login, $timeOrScore, $checkpointIndex);
		}

		// try to insert record ...
		$this->insertRecord($player, $record);
	}

	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Data/ManiaLive\Data.Listener::onPlayerNewBestTime()
	 */
	public function onPlayerNewBestTime($player, $best_old, $best_new)
	{
		Console::printDebug($player->login . ' drove new best!');

		// stunts is currently not implemented
		// and laps is implemented in onplayerfinishlap
		if ($this->storage->gameInfos->gameMode == GameInfos::GAMEMODE_STUNTS
			|| $this->storage->gameInfos->gameMode == GameInfos::GAMEMODE_LAPS)
		{
			return;
		}

		// if first record is driven during load, then insert into queue and
		// process later as soon as ready!
		if (!$this->isReady())
		{
			$callback = array($this, 'onPlayerNewBestTime');
			$this->registerWaitForReady($callback, $player, $best_old, $best_new);
			return;
		}

		// create record object ..
		$record = new Record();
		$record->best = $player->bestTime;
		$record->checks = $player->bestCheckpoints;
		$record->login = $player->login;
		$record->nickName = $player->nickName;
		$record->challenge = $this->challenge_current;

		$this->insertRecord($player, $record);
	}

	/**
	 *
	 * Enter description here ...
	 * @param $player
	 * @param $record
	 */
	protected function insertRecord($player, $record)
	{
		// validate record
		if (!$record->validate())
		{
			return;
		}

		// is this relevant for best times:
		// either the player's time is at least better than the last record or there are no more
		// than 30 records yet.
		if ($this->records_last == null
			|| $player->bestTime < $this->records_last->best
			|| $this->records_count < self::RECORDS_MAX)
		{
			// this is the first record of this player ...
			if (!array_key_exists($record->login, $this->records_by_login))
			{
				// insert new record to the end of the list ...
				$record->rank = $this->records_count + 1;
				$this->records[$this->records_count] = $record;
				$this->records_count++;

				// this player has a record now!
				$this->records_by_login[$record->login] = $record;

				// shift the record up until it is on the right position ...
				for ($i = $this->records_count - 1; $i > 0; $i--)
				{
					if ($this->records[$i]->best < $this->records[$i-1]->best)
						$this->swapRecords($i, $i-1);
					else
						break;
				}

				// player drove first record ...
				$this->records_new[] = $record;

				// send notification to all players
				if (self::$notifications)
				{
					if ($record->rank == 1)
					{
						$msg = $this->prepareMessage($record, self::$notifyNewFirstRecord);
						$this->connection->chatSendServerMessage($msg);
					}
					else
					{
						$msg = $this->prepareMessage($record, self::$notifyNewRecord);
						$this->connection->chatSendServerMessage($msg);
					}
				}

				$this->writeConsole($record->login . ' drove a new ' . $record->rank . '. record!');
			}

			// this player has a record already!
			else
			{
				// this time is worse than player's current record ...
				if ($this->records_by_login[$record->login]->best <= $record->best) return;

				// search for the old record in the leaderboard ...
				$old_rank = 0;
				for ($i = $this->records_count - 1; $i >= 0; $i--)
				{
					// if we found the old record, then update it
					if ($this->records[$i]->login == $record->login)
					{
						$this->records[$i]->best = $record->best;
						$this->records[$i]->checks = $record->checks;
						$record = $this->records[$i];
						$old_rank = $i;

						// move record forward ...
						for ($n = $i; $n > 0; $n--)
						{
							if ($this->records[$n]->best < $this->records[$n-1]->best)
								$this->swapRecords($n, $n-1);
							else
								break;
						}
						break;
					}
				}

				// send notification to all players
				if (self::$notifications)
				{
					if ($record->rank == 1)
					{
						if ($old_rank == 0)
						{
							$msg = $this->prepareMessage($record, self::$notifyImprovedFirstRecord);
							$this->connection->chatSendServerMessage($msg);
						}
						else
						{
							$msg = $this->prepareMessage($record, self::$notifyNewFirstRecord);
							$this->connection->chatSendServerMessage($msg);
						}
					}
					else
					{
						if ($record->rank != ($old_rank+1))
						{
							$msg = $this->prepareMessage($record, self::$notifyImprovedRecord);
							$this->connection->chatSendServerMessage($msg);
						}
						else
						{
							$msg = $this->prepareMessage($record, self::$notifyImprovedRecordTimeOnly);
							$this->connection->chatSendServerMessage($msg);
						}
					}
				}

				// player improved his record ...
				$this->records_new[] = $record;
				$this->writeConsole($record->login.' improved to ' . $record->rank . '. rank and was ' . ($old_rank+1) . '. before!');
			}

			// remove all records that are redundant
			while ($this->records_count > self::RECORDS_MAX)
			{
				$record = array_pop($this->records);
				if (isset($this->records_by_login[$record->login]))
				{
					unset($this->records_by_login[$record->login]);
					$this->records_count--;
				}
				$record->destroy();
			}

			// point to the last element of all records
			$this->records_last = $this->records[$this->records_count - 1];

			// update window information
			$wins = Leaderboard::GetAll();
			foreach ($wins as $window)
			{
				// refresh records for this window ...
				$window->clearRecords();
				foreach ($this->records as $record)
				{
					$entry = array
					(
						'Rank' => $record->rank,
						'Login' => $record->login,
						'NickName' => $record->nickName,
						'Time' => Time::fromTM($record->best)
					);
					$window->addRecord($entry);
				}
			}

			// redraw all windows
			Leaderboard::Redraw();

			// build notification ...
			$notification = 'just took ';
			$notification .= String::formatRank($record->rank);
			$notification .= ' record with a time of ';
			$notification .= Time::fromTM($record->best);
			$notification .= ' on ';
			$notification .= String::stripWideFonts($this->storage->currentChallenge->name);

			// send notifications on maniahome ...
			$runnable = new NotificationCall($notification, $record->login, 'tmtp://#join=' . $this->storage->serverLogin, 'BgRaceScore2', 'ScoreLink');
			ThreadPool::getInstance()->addCommand(new RunCommand($runnable));
		}
	}

	/**
	 * Replace certain keywords in the message that
	 * can be defined in the config file.
	 * @param Record $record
	 * @param string $message
	 */
	protected function prepareMessage(Record $record, $message)
	{
		$search = array(
			'%player%',
			'%time%',
			'%rank%'
		);

		$replace = array(
			String::stripAllTmStyle($record->nickName),
			Time::fromTM($record->best),
			String::formatRank($record->rank)
		);

		return str_replace($search, $replace, $message);
	}

	/**
	 * Swaps to records in the ranking table
	 * if they are in wrong order.
	 * @param $i
	 * @param $n
	 */
	protected function swapRecords($i, $n)
	{
		$temp = $this->records[$n];
		$this->records[$n] = $this->records[$i];
		$this->records[$i] = $temp;
		$this->records[$i]->rank = $i+1;
		$this->records[$n]->rank = $n+1;
	}

	/**
	 * Get an array with infromation needed by
	 * Dedimania for a player.
	 */
	protected function getPlayersArray()
	{
		$players = array();

		// parse players ...
		foreach ($this->storage->players as $player)
		{
			$players[] = array
			(
				'Login' => $player->login,
				'Nation' => $player->path,
				'TeamId' => $player->teamId,
				'IsSpec' => false,
				'Ranking' => $player->ladderRanking,
				'IsOff' => $player->isInOfficialMode
			);
		}

		// parse spectators ...
		foreach ($this->storage->spectators as $player)
		{
			$players[] = array
			(
				'Login' => $player->login,
				'Nation' => $player->path,
				'TeamId' => $player->teamId,
				'IsSpec' => true,
				'Ranking' => $player->ladderRanking,
				'IsOff' => $player->isInOfficialMode
			);
		}
		return $players;
	}

	/**
	 * Get an array with information about the server
	 * that is needed by Dedimania.
	 */
	protected function getServerInfoArray()
	{
		$sysinfo = $this->connection->getSystemInfo();

		return array
		(
			'SrvName' => $this->storage->server->name,
			'Comment' => $this->storage->server->comment,
			'Private' => isset($this->server->password),
			'SrvIP' => $sysinfo->publishedIp,
			'SrvPort' => $sysinfo->port,
			'XmlrpcPort' => Loader::$config->server->port,
			'NumPlayers' => count($this->storage->players),
			'MaxPlayers' => $this->storage->server->currentMaxPlayers,
			'NumSpecs' => count($this->storage->spectators),
			'MaxSpecs' => $this->storage->server->currentMaxSpectators,
			'LadderMode' => $this->storage->server->currentLadderMode
		);
	}

	/**
	 * Register a callback method that is
	 * executed as soon as the server is ready.
	 */
	public function registerWaitForReady()
	{
		$args = func_get_args();
		$callback = array_shift($args);

		$this->callback_queue[] = array
		(
			'callback' => $callback,
			'params' => $args
		);
	}

	/**
	 * Is Dedimania currently loading?
	 * @return bool
	 */
	public function isReady()
	{
		return $this->ready;
	}

	/**
	 * Dedimania starts to load records.
	 */
	protected function setBusy()
	{
		$this->ready = false;
	}

	/**
	 * Dedimania has finished loading.
	 * Inform everyone that has subscribed!
	 */
	protected function setReady()
	{
		$this->ready = true;

		while ($entry = array_shift($this->callback_queue))
		{
			if (is_callable($entry['callback']))
			{
				call_user_func_array($entry['callback'], $entry['params']);
			}
		}
	}

	function onUnload()
	{
		Leaderboard::EraseAll();
		parent::onUnload();
	}

	// threading listener
	function onThreadStart($thread) {}
	function onThreadDies($thread) {}
	function onThreadTimesOut($thread) {}

	// dedimania listener
	function onPlayerNewRank($player, $rank_old, $rank_new) {}
	function onPlayerNewBestScore($player, $score_old, $score_new) {}
}

// exception classes
class Exception extends \Exception {}
class ConfigurationException extends Exception {}
class NotConnectedException extends Exception {}
?>