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

namespace ManiaLive\DedicatedApi\Callback;

abstract class Adapter implements Listener
{	
	function onPlayerConnect($login, $isSpectator) {}
	function onPlayerDisconnect($login) {}
	function onPlayerChat($playerUid, $login, $text, $isRegistredCmd) {}
	function onPlayerManialinkPageAnswer($playerUid, $login, $answer,array $entries) {}
	function onEcho($internal, $public) {}
	function onServerStart() {}
	function onServerStop() {}
	function onBeginMatch() {}
	function onEndMatch($rankings) {}
	function onBeginMap($map, $warmUp, $matchContinuation) {}
	function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap) {}
	function onBeginRound() {}
	function onEndRound() {}
	function onStatusChanged($statusCode, $statusName) {}
	function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex) {}
	function onPlayerFinish($playerUid, $login, $timeOrScore) {}
	function onPlayerIncoherence($playerUid, $login) {} 
	function onBillUpdated($billId, $state, $stateName, $transactionId) {}
	function onTunnelDataReceived($playerUid, $login, $data) {} 
	function onMapListModified($curMapIndex, $nextMapIndex, $isListModified) {} 
	function onPlayerInfoChanged($playerInfo) {}
	function onManualFlowControlTransition($transition) {}
	function onVoteUpdated($stateName, $login, $cmdName, $cmdParam) {}
	function onModeScriptCallback($param1, $param2) {}
}

?>