<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLive\Data;

interface Listener extends \ManiaLive\Event\Listener
{
	/**
	 * Event lauch when a player beat his best Time
	 * @param \DedicatedApi\Structures\Player $player
	 * @param int $oldBest
	 * @param int $newBest
	 */
	function onPlayerNewBestTime($player, $oldBest, $newBest);
	
	/**
	 * Event lauch when player's rank change
	 * @param \DedicatedApi\Structures\Player $player
	 * @param int $oldRank
	 * @param int $newRank
	 */
	function onPlayerNewRank($player, $oldRank, $newRank);
	
	/**
	 * Event lauch when beat his best score
	 * @param \DedicatedApi\Structures\Player $player
	 * @param int $oldScore
	 * @param int $newScore
	 */
	function onPlayerNewBestScore($player, $oldScore, $newScore);
	
	/**
	 * Event lauch when the player change to spectator or to player
	 * @param \DedicatedApi\Structures\Player $player
	 * @param string $oldSide - it can take 2 values spectator or player
	 */
	function onPlayerChangeSide($player, $oldSide);
	
	/**
	 * Event launched when a player finishes a lap.
	 * @param \DedicatedApi\Structures\Player $player
	 * @param integer $time
	 * @param integer $nbLap
	 */
	function onPlayerFinishLap($player, $timeOrScore, $checkpoints, $nbLap);
}

?>