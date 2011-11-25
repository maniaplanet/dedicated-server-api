<?php

/**
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */
namespace ManiaLivePlugins\Nadeo\Matchs;

class Config extends \ManiaLib\Utils\Singleton
{
	public $gameMode = 'team';
	public $chatTime = 10000;
	public $finishTimeout = 15000;
	public $allWarmUpDuration = 1;
	public $disableRespawn = false;
	public $forceShowAllOpponents = 1;
	public $roundsPointsLimit = 10;
	public $roundsForcedLaps = 0;
	public $roundsUseNewRules = true;
	public $roundsPointsLimitNewRules = 5;
	public $teamPointsLimit = 2;
	public $teamMaxPoints = 6;
	public $teamUseNewRules = true;
	public $teamPointsLimitNewRules = 5;
	public $timeAttackLimit = 300000;
	public $timeAttackSynchStartPeriod = 0;
	public $lapsNbLaps = 5;
	public $lapsTimeLimit = 0; 
	public $cupPointsLimit = 100;
	public $cupRoundsPerMap = 5;
	public $cupNbWinners = 3;
	public $cupWarmUpDuration = 2;
	
	public $nbMap = 3;
	public $customPoints = array(10,6,4,3,1);
}

?>
