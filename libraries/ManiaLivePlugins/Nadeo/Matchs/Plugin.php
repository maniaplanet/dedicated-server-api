<?php

namespace ManiaLivePlugins\Nadeo\Matchs;

use ManiaLive\Data\Storage;
use ManiaLive\Utilities\Time;
use ManiaLive\DedicatedApi\Connection;
use ManiaLive\Utilities\Logger;
use ManiaLive\Features\Admin\AdminGroup;
use ManiaLive\DedicatedApi\Structures\GameInfos;
use ManiaLive\DedicatedApi\Structures\Challenge;
use ManiaLive\Utilities\String;

class Plugin extends \ManiaLive\PluginHandler\Plugin
{

	protected $inMatch = 0;
	protected $challengeCount = 0;
	
	protected $previousGamesInfos;
	
	function onInit()
	{
	}

	function onLoad()
	{
		$this->enableDedicatedEvents();

		$command = $this->registerChatCommand('start', 'start', 0, false,
			AdminGroup::get());
		$command->help = 'Start the match';
		$command->isPublic = true;

		$command = $this->registerChatCommand('stop', 'stop', 0, false,
			AdminGroup::get());
		$command->help = 'Stop the current match';
		$command->isPublic = true;
	}

	function stop()
	{
		$this->inMatch = 0;
		$connection->nextMap(false, true);
	}

	function start()
	{
//		$this->previousGamesInfos = $this->connection->getCurrentGameInfo();
		
		$config = Config::getInstance();

		switch($config->gameMode)
		{
			case 'timeAttack': $gameMode = GameInfos::GAMEMODE_TIMEATTACK;
				break;
			case 'script': $gameMode = GameInfos::GAMEMODE_SCRIPT;
				break;
			case 'stunt': $gameMode = GameInfos::GAMEMODE_STUNTS;
				break;
			case 'round': $gameMode = GameInfos::GAMEMODE_ROUNDS;
				break;
			case 'lap': $gameMode = GameInfos::GAMEMODE_LAPS;
				break;
			case 'team': $gameMode = GameInfos::GAMEMODE_TEAM;
				break;
		}

		$matchSetting = array();
		$matchSetting['AllWarmUpDuration'] = (int)$config->allWarmUpDuration;
		$matchSetting['GameMode'] = $gameMode;
		$matchSetting['ChatTime'] = (int)$config->chatTime;
		$matchSetting['CupNbWinners'] = (int)$config->cupNbWinners;
		$matchSetting['CupPointsLimit'] = (int)$config->cupPointsLimit;
		$matchSetting['CupWarmUpDuration'] = (int)$config->cupWarmUpDuration;
		$matchSetting['DisableRespawn'] = (bool)$config->disableRespawn;
		$matchSetting['FinishTimeout'] = (int)$config->finishTimeout;
		$matchSetting['ForceShowAllOpponents'] = (bool)$config->forceShowAllOpponents;
		$matchSetting['LapsNbLaps'] = (int)$config->lapsNbLaps;
		$matchSetting['LapsTimeLimit'] = (int)$config->lapsTimeLimit;
		$matchSetting['RoundsForcedLaps'] = (int)$config->roundsForcedLaps;
		$matchSetting['RoundsPointsLimit'] = (int)$config->roundsPointsLimit;
		$matchSetting['RoundsPointsLimitNewRules'] = (int)$config->roundsPointsLimitNewRules;
		$matchSetting['RoundsUseNewRules'] = (bool)$config->roundsUseNewRules;
		$matchSetting['TeamMaxPoints'] = (int)$config->teamMaxPoints;
		$matchSetting['TeamPointsLimit'] = (int)$config->teamPointsLimit;
		$matchSetting['TeamPointsLimitNewRules'] = (int)$config->teamPointsLimitNewRules;
		$matchSetting['TeamUseNewRules'] = (bool)$config->teamUseNewRules;
		$matchSetting['TimeAttackLimit'] = (int)$config->timeAttackLimit;
		$matchSetting['TimeAttackSynchStartPeriod'] = (int)$config->timeAttackSynchStartPeriod;

		$this->inMatch = -1;

		$this->connection->setGameInfos($matchSetting);
		
		if($config->teamUseNewRules)
			$teamPointsLimit = (int)$config->teamPointsLimitNewRules;
		else
			$teamPointsLimit = (int)$config->teamPointsLimit;
		$this->connection->setTeamPointsLimit($teamPointsLimit);

		$this->connection->nextMap(false, true);
	}
	
	function onBeginMap($map, $warmUp, $matchContinuation)
	{
		if($this->inMatch == -1)
		{
			$this->inMatch = 1;
		}
		if($this->inMatch === 1)
		{
			$this->challengeCount++;
		}
	}

	function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap,
		$restartMap)
	{
		if($this->inMatch === 1 && !$wasWarmUp)
		{
			$logName = $this->storage->server->name.'.csv';
			file_put_contents(APP_ROOT.'logs/'.$logName,'Map nb '.$this->challengeCount."\n", FILE_APPEND);
			file_put_contents(APP_ROOT.'logs/'.$logName,"rank;team;score\n", FILE_APPEND);
			foreach($rankings as $value)
			{
				file_put_contents(APP_ROOT.'logs/'.$logName,sprintf("%d;%s;%d\n",$value['Rank'], $value['Login'], $value['Score']), FILE_APPEND);
			}

			if(!$this->challengeCount == Config::getInstance()->nbMap)
			{
				$this->stop();
			}
		}
	}
}