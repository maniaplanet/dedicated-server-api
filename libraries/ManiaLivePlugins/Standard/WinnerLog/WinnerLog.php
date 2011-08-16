<?php

namespace ManiaLivePlugins\Standard\WinnerLog;

use ManiaLive\Utilities\Time;
use ManiaLive\Utilities\Logger;
use ManiaLive\Data\Storage;

class WinnerLog extends \ManiaLive\PluginHandler\Plugin
{
	function onInit()
	{
		$this->setVersion(1);
	}
	
	function onLoad()
	{
		$this->enableDedicatedEvents();
	}
	
	function onEndRace($rankings, $challenge)
	{
		$i = 0;
		$ladderRank = 0;
		$score = 0;
		$log = Logger::getLog('Winners');
		
		$log->write("Rankings for '{$this->storage->currentChallenge->name}' ({$this->storage->currentChallenge->uId}):" . APP_NL);
		
		if ($this->storage->gameInfos->gameMode == 4)
			$log->write("Bronze: {$this->storage->currentChallenge->bronzeTime}, Silver: {$this->storage->currentChallenge->silverTime}, Gold: {$this->storage->currentChallenge->goldTime}, Author: {$this->storage->currentChallenge->authorTime}" . APP_NL);
		else
			$log->write("Bronze: ".Time::fromTM($this->storage->currentChallenge->bronzeTime).", Silver: ".Time::fromTM($this->storage->currentChallenge->silverTime).", Gold: ".Time::fromTM($this->storage->currentChallenge->goldTime).", Author: ".Time::fromTM($this->storage->currentChallenge->authorTime) . APP_NL);
		
		while (($rank = array_shift($rankings)) && $i++ < Config::getInstance()->maxRankingLogged)
		{			
			if (isset($this->storage->players[$rank['Login']]))
			{
				$player = $this->storage->players[$rank['Login']];
				$ladderRank = $player->ladderStats['PlayerRankings'][0]['Ranking'];
			}
			
			if ($this->storage->gameInfos->gameMode == 4)
				$score = $rank['Score'];
			else
				$score = Time::fromTM($rank['BestTime']);
			
			$log->write("{$rank['Rank']}.\t{$rank['Login']}\t{$rank['NickName']}\t$score\t$ladderRank" . APP_NL);
		}
		
		$log->write(APP_NL);
	}
}
?>