<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\DedicatedApi\Callback;

abstract class Adapter implements Listener
{	
	function onPlayerConnect($login, $isSpectator) {}
	function onPlayerDisconnect($login) {}
	function onPlayerChat($playerUid, $login, $text, $isRegistredCmd) {}
	function onPlayerManialinkPageAnswer($playerUid, $login, $answer) {}
	function onEcho($internal, $public) {}
	function onServerStart() {}
	function onServerStop() {}
	function onBeginRace($challenge) {}
	function onEndRace($rankings, $challenge) {}
	function onBeginChallenge($challenge, $warmUp, $matchContinuation) {}
	function onEndChallenge($rankings, $challenge, $wasWarmUp, $matchContinuesOnNextChallenge, $restartChallenge) {}
	function onBeginRound() {}
	function onEndRound() {}
	function onStatusChanged($statusCode, $statusName) {}
	function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex) {}
	function onPlayerFinish($playerUid, $login, $timeOrScore) {}
	function onPlayerIncoherence($playerUid, $login) {} 
	function onBillUpdated($billId, $state, $stateName, $transactionId) {}
	function onTunnelDataReceived($playerUid, $login, $data) {} 
	function onChallengeListModified($curChallengeIndex, $nextChallengeIndex, $isListModified) {} 
	function onPlayerInfoChanged($playerInfo) {}
	function onManualFlowControlTransition($transition) {}
}

?>