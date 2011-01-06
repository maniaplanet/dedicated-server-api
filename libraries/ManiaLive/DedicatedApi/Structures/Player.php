<?php
/**
 * Represents a Dedicated TrackMania Server Player
 * @copyright NADEO (c) 2010
 */
namespace ManiaLive\DedicatedApi\Structures;

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
	public $isConnected = true;

	//Flags details
	public $forceSpectator;
	public $isPodiumReady;
	public $isUsingStereoscopy;

	//SpectatorStatus details
	public $spectator;
	public $temporarySpectator;
	public $pureSpectator;
	public $autoTarget;
	public $currentTargetId;

	function getArrayFromPath()
	{
		return explode('|', $this->path);
	}

	/**
	 * @return Player
	 */
	static public function fromArray($array)
	{
		$object = parent::fromArray($array);

		$object->skins = Skin::fromArrayOfArray($object->skins);
		$object->forceSpectator = $object->flags % 10;
		//Detail flags
		$object->isReferee = ($object->flags / 10) % 10;
		$object->isPodiumReady = ($object->flags /100) % 10;
		$object->isUsingStereoscopy = (int)($object->flags /1000) % 10;
		//Details spectatorStatus
		$object->spectator = $object->spectatorStatus % 10;
		$object->temporarySpectator = ($object->spectatorStatus /10) % 10;
		$object->pureSpectator = ($object->spectatorStatus /100) % 10;
		$object->autoTarget = ($object->spectatorStatus /1000) % 10;
		$object->currentTargetId = (int)($object->spectatorStatus /10000);
		
		return $object;
	}
}
?>