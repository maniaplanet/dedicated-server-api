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
	 * @param \ManiaLive\DedicatedApi\Structures\Player $player
	 * @param int $best_old
	 * @param int $best_new
	 */
	function onPlayerNewBestTime($player, $best_old, $best_new);
	
	/**
	 * Event lauch when player's rank change
	 * @param \ManiaLive\DedicatedApi\Structures\Player $player
	 * @param int $rank_old
	 * @param int $rank_new
	 */
	function onPlayerNewRank($player, $rank_old, $rank_new);
	
	/**
	 * Event lauch when beat his best score
	 * @param \ManiaLive\DedicatedApi\Structures\Player $player
	 * @param int $score_old
	 * @param int $score_new
	 */
	function onPlayerNewBestScore($player, $score_old, $score_new);
	
	/**
	 * Event lauch when the player change to spectator or to player
	 * @param \ManiaLive\DedicatedApi\Structures\Player $player
	 * @param string $oldSide - it can take 2 values spectator or player
	 */
	function onPlayerChangeSide($player, $oldSide);
}

?>