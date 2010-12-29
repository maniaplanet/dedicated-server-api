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
		if(!is_array($array)) return $array;
		$object = new static;
		foreach($array as $key=>$value)
		{
			$key = lcfirst($key);
			$object->$key = $value;
			switch($key)
			{
				case 'flags':
					$object->forceSpectator = $value % 10;
					$object->isReferee = ($value / 10) % 10;
					$object->isPodiumReady = ($value /100) % 10;
					$object->isUsingStereoscopy = (int)($value /1000) % 10;
					break;
				case 'spectatorStatus':
					$object->spectator = $value % 10;
					$object->temporarySpectator = ($value /10) % 10;
					$object->pureSpectator = ($value /100) % 10;
					$object->autoTarget = ($value /1000) % 10;
					$object->currentTargetId = (int)($value /10000);
					break;
			}
		}
		return $object;
	}
}
?>