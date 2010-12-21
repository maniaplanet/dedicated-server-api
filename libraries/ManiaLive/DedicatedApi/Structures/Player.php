<?php
/**
 *
 * Represents a Dedicated TrackMania Server Player
 * @author Philippe Melot
 * @copyright NADEO (c) 2010
 * @package ManiaMod
 * @subpackage Structures
 *
 */
namespace ManiaLive\DedicatedApi\Structures;

/**
 *
 * Represents a Dedicated TrackMania Server Player
 * @author Philippe Melot
 * @copyright NADEO (c) 2010
 *
 */
class Player extends AbstractStructure
{
	public $playerId;
	public $login;
	public $nickName;
	public $teamId;
	public $path;
	public $language;
	public $clientVersion;
	public $clientName;
	public $iPAddress;
	public $downloadRate;
	public $uploadRate;
	public $isSpectator;
	public $isInOfficialMode;
	public $isReferee;
	public $avatar;
	public $skins;
	public $ladderStats;
	public $hoursSinceZoneInscription;
	public $onlineRights;
	public $rank;
	public $bestTime;
	public $bestCheckpoints;
	public $score;
	public $nbrLapsFinished;
	public $ladderScore;
	public $stateUpdateLatency;
	public $stateUpdatePeriod;
	public $latestNetworkActivity;
	public $packetLossRate;
	public $spectatorStatus;
	public $ladderRanking;
	public $flags;
	
	function getArrayFromPath()
	{
		return explode('|', $this->path);
	}
}
?>