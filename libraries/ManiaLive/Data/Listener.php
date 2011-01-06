<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\Data;

interface Listener extends \ManiaLive\Event\Listener
{
	function onPlayerNewBestTime($player, $best_old, $best_new);
	function onPlayerNewRank($player, $rank_old, $rank_new);
	function onPlayerNewBestScore($player, $score_old, $score_new);
	function onPlayerChangeSide($player, $oldSide);
}

?>