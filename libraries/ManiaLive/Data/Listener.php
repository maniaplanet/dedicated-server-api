<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 1709 $:
 * @author      $Author: svn $:
 * @date        $Date: 2011-01-07 14:06:13 +0100 (ven., 07 janv. 2011) $:
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