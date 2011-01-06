<?php
/**
 * Represents the Game Infos of a Dedicated TrackMania Server
 * @copyright NADEO (c) 2010
 */
namespace ManiaLive\DedicatedApi\Structures;

class GameInfos extends AbstractStructure
{
	public $gameMode;
	public $nbChallenge;
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
	public $cupRoundsPerChallenge;
	public $cupNbWinners;
	public $cupWarmUpDuration;
}
?>