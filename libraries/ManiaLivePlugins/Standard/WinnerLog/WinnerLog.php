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
	
	function onEndMatch($rankings, $map)
	{
		$i = 0;
		$ladderRank = 0;
		$score = 0;
		$log = Logger::getLog('Winners');
		
		$log->write("Rankings for '{$this->storage->currentMap->name}' ({$this->storage->currentMap->uId}):");
		
		if ($this->storage->gameInfos->gameMode == 4)
			$log->write("Bronze: {$this->storage->currentMap->bronzeTime}, Silver: {$this->storage->currentMap->silverTime}, Gold: {$this->storage->currentMap->goldTime}, Author: {$this->storage->currentMap->authorTime}");
		else
			$log->write("Bronze: ".Time::fromTM($this->storage->currentMap->bronzeTime).", Silver: ".Time::fromTM($this->storage->currentMap->silverTime).", Gold: ".Time::fromTM($this->storage->currentMap->goldTime).", Author: ".Time::fromTM($this->storage->currentMap->authorTime));
		
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
			
			$log->write("{$rank['Rank']}.\t{$rank['Login']}\t{$rank['NickName']}\t$score\t$ladderRank");
		}
	}
}
?>