<?php

namespace ManiaLivePlugins\Standard\MapVote;

use ManiaLib\Gui\Cards\Dialogs\OneButton;
use ManiaLive\DedicatedApi\Connection as DediApi;
use ManiaLive\Database\MySQL\Connection;
use ManiaLive\Data\Storage;
use ManiaLivePlugins\Standard\MapVote\Gui\Windows\Vote;

class MapVote extends \ManiaLive\PluginHandler\Plugin
{
	public $score;
	
	const VOTE_GOOD = 1;
	const VOTE_BAD = -1;

	function onInit()
	{
		$this->setVersion(1);
	}

	function onLoad()
	{
		// establish database connection
		$this->enableDatabase();
		// enable dedicated server events
		$this->enableDedicatedEvents();

		// register chat command to give a good rating
		$cmd = $this->registerChatCommand('++', 'voteGood', 0, true);
		$cmd->help = 'Use this if you like the current track to increase its rating.';

		// register chat command to give a bad rating
		$cmd = $this->registerChatCommand('--', 'voteBad', 0, true);
		$cmd->help = 'Use this if you do not like the current track and want it to be removed.';

		if(!$this->db->tableExists('votes'))
		{
			$this->db->execute(
				'CREATE TABLE IF NOT EXISTS `Votes` ('.
				'`login` VARCHAR(25) NOT NULL,'.
				'`challengeUid` VARCHAR(27) NOT NULL,'.
				'`vote` TINYINT(1) NOT NULL,'.
				'`serverLogin` VARCHAR(25) NOT NULL,'.
				'`date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,'.
				'PRIMARY KEY (`serverLogin`, `challengeUid`, `login`)'.
				') '.
				'COMMENT=\'Used to rate challenges by players.\' '.
				'COLLATE=\'utf8_general_ci\' '.
				'ENGINE=InnoDB '.
				'ROW_FORMAT=DEFAULT;');
		}
		
		Vote::Initialize($this);
	}

	function onBeginChallenge($challenge, $warmUp, $matchContinuation)
	{
		$challengeUid = $this->storage->currentChallenge->uId;
		$this->score = $this->getChallengeScore($challengeUid);
		
		if(!count($this->storage->players))
			return;
		
		$votes = $this->getPlayerVotes(array_keys($this->storage->players), $challengeUid);
		
		foreach ($this->storage->players as $login => $player)
		{
			$vote = Vote::Create($login);
			if (isset($votes[$login]))
				$vote->currentVote = $votes[$login];
			else
				$vote->currentVote = null;
			$vote->setPosition(49, -32.2);
			$vote->setScale(0.8);
			$vote->show();
		}
	}

	function onEndRace($rankings, $challenge)
	{
		// refresh votes for every player ...
		Vote::Redraw();
	}

	function onPlayerConnect($login, $isSpectator)
	{
		$vote = Vote::Create($login);
		$vote->currentVote = $this->getPlayerVote($login, $this->storage->currentChallenge->uId);
		$vote->setPosition(49, -32.2);
		$vote->setScale(0.8);
		$vote->show();
	}

	function onPlayerDisconnect($login)
	{
		Vote::Erase($login);
	}
	
	function onReady()
	{
		$this->onBeginChallenge(null, null, null);
	}

	function voteGood($login, $window = false)
	{
		if(!isset($this->storage->players[$login]))
			return;
		$player = $this->storage->players[$login];
		
		if ($this->doVote($login, self::VOTE_GOOD))
		{
			$vote = Vote::Create($login);
			if($vote->currentVote == self::VOTE_BAD)
			{
				$this->score['bad']--;
				$this->score['good']++;
			}
			elseif(!$vote->currentVote)
			{
				$this->score['good']++;
			}
			$vote->currentVote = self::VOTE_GOOD;
			$vote->show();

			if(!$window)
				\ManiaLive\DedicatedApi\Connection::getInstance()->chatSendServerMessage('$0c0Your vote has successfully been updated!', $player);
		}
		else if (!$window)
			\ManiaLive\DedicatedApi\Connection::getInstance()->chatSendServerMessage('$c00Your vote has not been changed!', $player);
	}

	function voteBad($login, $window = false)
	{
		$player = null;
		if (isset($this->storage->players[$login]))
			$player = $this->storage->players[$login];
		else
			return;

		if($this->doVote($login, self::VOTE_BAD))
		{
			$vote = Vote::Create($login);
			if($vote->currentVote == self::VOTE_GOOD)
			{
				$this->score['bad']++;
				$this->score['good']--;
			}
			elseif(!$vote->currentVote)
			{
				$this->score['bad']++;
			}
			$vote->currentVote = self::VOTE_BAD;
			$vote->show();

			if(!$window)
				\ManiaLive\DedicatedApi\Connection::getInstance()->chatSendServerMessage('$0c0Your vote has successfully been updated!', $player);
		}
		else if(!$window)
			\ManiaLive\DedicatedApi\Connection::getInstance()->chatSendServerMessage('$c00Your vote has not been changed!', $player);
	}

	function getPlayerVotes($logins, $challengeUid)
	{
		$votes = array();
		$recordset = $this->db->query(
				'SELECT `login`, `vote` FROM `Votes` WHERE `login` IN (%s) AND `challengeUid`=%s AND `serverLogin`=%s',
				implode(',', array_map(array($this->db, 'quote'), $logins)),
				$this->db->quote($challengeUid),
				$this->db->quote($this->storage->serverLogin)
		);

		while($vote = $recordset->fetchRow())
		{
			$votes[$vote[0]] = intval($vote[1]);
		}

		return $votes;
	}

	function getPlayerVote($login, $challengeUid)
	{
		$recordset = $this->db->query(
				'SELECT `vote` FROM `Votes` WHERE `login`=%s AND `challengeUid`=%s AND `serverLogin`=%s',
				$this->db->quote($login),
				$this->db->quote($challengeUid),
				$this->db->quote($this->storage->serverLogin));
		return $recordset->fetchScalar();
	}

	function doVote($login, $vote)
	{
		$this->db->execute(
				'INSERT INTO `Votes` (`login`, `challengeUid`, `vote`, `serverLogin`) ' .
				'VALUES (%s, %s, %d, %s) ' .
				'ON DUPLICATE KEY UPDATE vote=VALUES(vote)',
				$this->db->quote($login),
				$this->db->quote($this->storage->currentChallenge->uId),
				$vote,
				$this->db->quote($this->storage->serverLogin));
		
		return ($this->db->affectedRows() > 0);
	}

	function getChallengeScore($challengeUid)
	{
		$recordset = $this->db->query(
				'SELECT COUNT(*), SUM(vote) FROM `Votes` WHERE `challengeUid`=%s AND `serverLogin`=%s',
				$this->db->quote($challengeUid),
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
		Vote::EraseAll();
		Vote::Unload();
		parent::onUnload();
	}

}

?>
