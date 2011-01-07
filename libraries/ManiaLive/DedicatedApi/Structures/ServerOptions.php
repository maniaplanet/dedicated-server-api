<?php
/**
 * Represents the Options of a TrackMania Dedicated Server
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 1709 $:
 * @author      $Author: svn $:
 * @date        $Date: 2011-01-07 14:06:13 +0100 (ven., 07 janv. 2011) $:
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