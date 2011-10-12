<?php
/**
 * Represents the Game Infos of a Dedicated TrackMania Server
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */
namespace ManiaLive\DedicatedApi\Structures;

class GameInfos extends AbstractStructure
{
	/**
	 * Game Modes
	 */
	const GAMEMODE_SCRIPT = 0;
	const GAMEMODE_ROUNDS = 1;
	const GAMEMODE_TIMEATTACK = 2;
	const GAMEMODE_TEAM = 3;
	const GAMEMODE_LAPS = 4;
	const GAMEMODE_CUP = 5;
	const GAMEMODE_STUNTS = 6;
	
	public $gameMode;
	public $nbMap;
	public $chatTime;
	public $finishTimeout;
	public $allWarmUpDuration;
	public $disableRespawn;
	public $forceShowAllOpponents;
	public $roundsPointsLimit;
	public $roundsForcedLaps;
	public $roundsUseNewRules;
	public $roundsPointsLimitNewRules;
	public $teamPointsLimit;
	public $teamMaxPoints;
	public $teamUseNewRules;
	public $teamPointsLimitNewRules;
	public $timeAttackLimit;
	public $timeAttackSynchStartPeriod;
	public $lapsNbLaps;
	public $lapsTimeLimit; 
	public $cupPointsLimit;
	public $cupRoundsPerMap;
	public $cupNbWinners;
	public $cupWarmUpDuration;
}
?>