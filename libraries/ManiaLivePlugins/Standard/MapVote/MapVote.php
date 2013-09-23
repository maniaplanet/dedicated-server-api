<?php
/**
 * MapVote Plugin - Is the current liked by players?
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\Standard\MapVote;

use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;
use ManiaLivePlugins\Standard\MapVote\Gui\Windows\Vote;

class MapVote extends \ManiaLive\PluginHandler\Plugin
{
	public $score;
	public $voteGoodAction;
	public $voteBadAction;
	
	const VOTE_GOOD = 1;
	const VOTE_BAD = -1;

	function onInit()
	{
		$this->setVersion('2.0.0');
	}

	function onLoad()
	{
		Vote::Initialize($this);

		// register chat command to give a good rating
		$cmd = $this->registerChatCommand('++', 'voteGood', 0, true);
		$cmd->help = 'Use this if you like the current track to increase its rating.';

		// register chat command to give a bad rating
		$cmd = $this->registerChatCommand('--', 'voteBad', 0, true);
		$cmd->help = 'Use this if you do not like the current track and want it to be removed.';
		
		$this->enableDedicatedEvents(ServerEvent::ON_PLAYER_CONNECT | ServerEvent::ON_BEGIN_MAP);
		$this->enableDatabase();
		
		if(!$this->db->tableExists('Votes'))
		{
			$this->db->execute(
				'CREATE TABLE IF NOT EXISTS `Votes` ('.
				'`login` VARCHAR(25) NOT NULL,'.
				'`lp` INT UNSIGNED NULL, '.
				'`mapUid` VARCHAR(27) NOT NULL, '.
				'`mapName` VARCHAR(75) NOT NULL, '.
				'`vote` TINYINT(1) NOT NULL,'.
				'`serverLogin` VARCHAR(25) NOT NULL,'.
				'`date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,'.
				'PRIMARY KEY (`serverLogin`, `mapUid`, `login`)'.
				') '.
				'COMMENT=\'Used to rate maps by players.\' '.
				'COLLATE=\'utf8_general_ci\' '.
				'ENGINE=InnoDB '.
				'ROW_FORMAT=DEFAULT;');
		}
	}
	
	function onReady()
	{
		$this->onBeginMap(null, null, null);
	}

	function onPlayerConnect($login, $isSpectator)
	{
		$vote = Vote::Create($login);
		$vote->currentVote = $this->getPlayerVote($login, $this->storage->currentMap->uId);
		$vote->setPosition(139, 30);
		$vote->setScale(0.8);
		$vote->show();
	}

	function onBeginMap($map, $warmUp, $matchContinuation)
	{
		$mapUid = $this->storage->currentMap->uId;
		$this->score = $this->getMapScore($mapUid);
		Vote::Update();
		
		if(!count($this->storage->players))
			return;
		
		$votes = $this->getPlayerVotes(array_keys($this->storage->players), $mapUid);
		
		foreach($this->storage->players as $login => $player)
		{
			$vote = Vote::Create($login);
			if(isset($votes[$login]))
				$vote->currentVote = $votes[$login];
			else
				$vote->currentVote = null;
			$vote->setPosition(139, 30);
			$vote->setScale(0.8);
			$vote->show();
		}
	}

	function voteGood($login, $window = false)
	{
		if($this->doVote($login, self::VOTE_GOOD))
		{
			$vote = Vote::Create($login);
			if($vote->currentVote == self::VOTE_BAD)
			{
				$this->score['bad']--;
				$this->score['good']++;
			}
			else if(!$vote->currentVote)
				$this->score['good']++;
			$vote->currentVote = self::VOTE_GOOD;
			Vote::Update();
			Vote::RedrawAll();

			$this->connection->chatSendServerMessage('$0c0Your vote has successfully been updated!', $login);
		}
		else 
			$this->connection->chatSendServerMessage('$c00Your vote has not been changed!', $login);
	}

	function voteBad($login, $window = false)
	{
		if($this->doVote($login, self::VOTE_BAD))
		{
			$vote = Vote::Create($login);
			if($vote->currentVote == self::VOTE_GOOD)
			{
				$this->score['bad']++;
				$this->score['good']--;
			}
			else if(!$vote->currentVote)
				$this->score['bad']++;
			$vote->currentVote = self::VOTE_BAD;
			Vote::Update();
			Vote::RedrawAll();

			$this->connection->chatSendServerMessage('$0c0Your vote has successfully been updated!', $login);
		}
		else 
			$this->connection->chatSendServerMessage('$c00Your vote has not been changed!', $login);
	}

	function getPlayerVotes($logins, $mapUid)
	{
		$votes = array();
		$recordset = $this->db->execute(
				'SELECT `login`, `vote` FROM `Votes` WHERE `login` IN (%s) AND `mapUid`=%s AND `serverLogin`=%s',
				implode(',', array_map(array($this->db, 'quote'), $logins)),
				$this->db->quote($mapUid),
				$this->db->quote($this->storage->serverLogin)
		);

		while($vote = $recordset->fetchRow())
			$votes[$vote[0]] = intval($vote[1]);

		return $votes;
	}

	function getPlayerVote($login, $mapUid)
	{
		$recordset = $this->db->execute(
				'SELECT `vote` FROM `Votes` WHERE `login`=%s AND `mapUid`=%s AND `serverLogin`=%s',
				$this->db->quote($login),
				$this->db->quote($mapUid),
				$this->db->quote($this->storage->serverLogin));
		return $recordset->fetchSingleValue();
	}

	function doVote($login, $vote)
	{
		$player = $this->storage->players[$login];
		$this->db->execute(
				'INSERT INTO `Votes` (`login`, `lp`, `mapUid`, `mapName`, `vote`, `serverLogin`) ' .
				'VALUES (%s, %d, %s, %s, %d, %s) ' .
				'ON DUPLICATE KEY UPDATE vote=VALUES(vote), lp=VALUES(lp), mapName=VALUES(mapName)',
				$this->db->quote($login),
				$player->ladderStats['PlayerRankings'][0]['Score'],
				$this->db->quote($this->storage->currentMap->uId),
				$this->db->quote(\ManiaLib\Utils\Formatting::stripStyles($this->storage->currentMap->name)),
				$vote,
				$this->db->quote($this->storage->serverLogin));
		
		return $this->db->affectedRows() > 0;
	}

	function getMapScore($mapUid)
	{
		$recordset = $this->db->execute(
				'SELECT COUNT(*), SUM(vote) FROM `Votes` WHERE `mapUid`=%s AND `serverLogin`=%s',
				$this->db->quote($mapUid),
				$this->db->quote($this->storage->serverLogin));
		$row = $recordset->fetchRow();

		$total = intval($row[0]);
		$sum = intval($row[1]);
		$good = ($sum + $total) / 2;
		$bad = $total - $good;

		return array (
			'total' => $total,
			'good' => $good,
			'bad' => $bad
		);
	}

	function onUnload()
	{
		Vote::Unload();
		Vote::EraseAll();
		parent::onUnload();
	}
}

?>
