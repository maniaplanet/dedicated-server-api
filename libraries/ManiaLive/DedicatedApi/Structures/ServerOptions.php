<?php
/**
 * Represents the Options of a TrackMania Dedicated Server
 * @copyright NADEO (c) 2010
 */
namespace ManiaLive\DedicatedApi\Structures;

class ServerOptions extends AbstractStructure
{
	public $name;
	public $comment;
	public $password;
	public $passwordForSpectator;
	public $hideServer;
	public $currentMaxPlayers;
	public $nextMaxPlayers;
	public $currentMaxSpectators;
	public $nextMaxSpectators;
	public $isP2PUpload;
	public $isP2PDownload;
	public $currentLadderMode;
	public $nextLadderMode;
	public $ladderServerLimitMax;
	public $ladderServerLimitMin;
	public $currentVehicleNetQuality;
	public $nextVehicleNetQuality;
	public $currentCallVoteTimeOut;
	public $nextCallVoteTimeOut;
	public $callVoteRatio;
	public $allowChallengeDownload;
	public $autoSaveReplays;
	public $autoSaveValidationReplays;
	public $refereePassword;
	public $refereeMode;
	public $currentUseChangingValidationSeed;
	public $nextUseChangingValidationSeed;
}