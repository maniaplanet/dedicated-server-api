<?php
/**
 * WinnerLog Plugin - Save winners in a file
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\Standard\WinnerLog;

use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;
use DedicatedApi\Structures\GameInfos;
use ManiaLive\Utilities\Time;

class WinnerLog extends \ManiaLive\PluginHandler\Plugin
{
	function onInit()
	{
		$this->setVersion('1');
	}
	
	function onLoad()
	{
		$this->enableDedicatedEvents(ServerEvent::ON_END_MATCH);
	}
	
	function onEndMatch($rankings)
	{
		$this->writeLog("Rankings for '{$this->storage->currentMap->name}' ({$this->storage->currentMap->uId}):");
		
		if($this->storage->gameInfos->gameMode == GameInfos::GAMEMODE_STUNTS)
			$this->writeLog('Bronze: '.$this->storage->currentMap->bronzeTime.', Silver: '.$this->storage->currentMap->silverTime.', Gold: '.$this->storage->currentMap->goldTime.', Author: '.$this->storage->currentMap->authorTime);
		else
			$this->writeLog('Bronze: '.Time::fromTM($this->storage->currentMap->bronzeTime).', Silver: '.Time::fromTM($this->storage->currentMap->silverTime).', Gold: '.Time::fromTM($this->storage->currentMap->goldTime).', Author: '.Time::fromTM($this->storage->currentMap->authorTime));
		
		foreach(array_slice($rankings, 0, Config::getInstance()->maxRankingLogged) as $rank)
		{
			$ladderRank = 0;
			$score = 0;
			
			$player = $this->storage->getPlayerObject($rank['Login']);
			if($player)
				$ladderRank = $player->ladderStats['PlayerRankings'][0]['Ranking'];
			
			if($this->storage->gameInfos->gameMode == GameInfos::GAMEMODE_STUNTS)
				$score = $rank['Score'];
			else
				$score = Time::fromTM($rank['BestTime']);
			
			$this->writeLog("{$rank['Rank']}.\t{$rank['Login']}\t{$rank['NickName']}\t$score\t$ladderRank");
		}
	}
}
?>