<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\DedicatedApi\Callback;

interface Listener extends  \ManiaLive\Event\Listener
{
	/**
	 * Method called when a Player join the server
	 * @param string $login
	 * @param bool $isSpectator
	 */
	function onPlayerConnect($login, $isSpectator);
	/**
	 * Method called when a Player quit the server
	 * @param string $login
	 */
	function onPlayerDisconnect($login);
	/**
	 * Method called when a Player quit the server
	 * @param int $playerUid
	 * @param string $login
	 * @param string $text
	 * @param bool $isRegistredCmd
	 */
	function onPlayerChat($playerUid, $login, $text, $isRegistredCmd);
	/**
	 * Method called when a Answer to a Manialink Page
	 * difference with previous TM: this is not called if the player doesn't answer, and thus '0' is also a valid answer.
	 * @param int $playerUid
	 * @param string $login
	 * @param int $answer
	 */
	function onPlayerManialinkPageAnswer($playerUid, $login, $answer);
	/**
	 * Method called when the dedicated Method Echo is called
	 * @param string $internal
	 * @param string $public
	 */
	function onEcho($internal, $public);
	/**
	 * Method called when the server starts
	 */
	function onServerStart();
	/**
	 * Method called when the server stops
	 */
	function onServerStop();
	/**
	 * Method called when the Race Begin
	 * struct SChallengeInfo is like the object Challenge
	 * struct SChallengeInfo
	 * {
	 *	string Uid;
	 *	string Name;
	 *	string FileName;
	 *	string Author;
	 *	string Environnement;
	 *	string Mood;
	 *	int BronzeTime;
	 *	int SilverTime;
	 *	int GoldTime;
	 *	int AuthorTime;
	 *	int CopperPrice;
	 *	bool LapRace;
	 *	int NbLaps;
	 *	int NbCheckpoints;
	 * } 
	 * @param array[ChallengeInfo] $challenge
	 */
	function onBeginRace($challenge);
	/**
	 * Method called when the Race Ended
	 * struct of SPlayerRanking is a part of the structure of DedicatedApi\Structures\Player object
	 * struct ChallengeInfo is like the object Challenge
	 * struct SPlayerRanking
	 * {
	 *	string Login;
	 *	string NickName;
	 *	int PlayerId;
	 *	int Rank;
	 *	int BestTime;
	 *	array[int] BestCheckpoints;
	 *	int Score;
	 *	int NbrLapsFinished;
	 *	double LadderScore;
	 * } 
	 * @param array[SPlayerRanking] $rankings
	 * @param SChallengeInfo $challenge
	 */
	function onEndRace($rankings, $challenge);
	/**
	 * Method called when a challenge begin
	 * @param SChallengeInfo $challenge
	 * @param bool $warmUp
	 * @param bool $matchContinuation
	 */
	function onBeginChallenge($challenge, $warmUp, $matchContinuation);
	/**
	 * Method called when a challenge end
	 * @param array[SPlayerRanking] $rankings
	 * @param SChallengeInfo $challenge
	 * @param bool $wasWarmUp
	 * @param bool $matchContinuesOnNextChallenge
	 * @param bool $restartChallenge
	 */
	function onEndChallenge($rankings, $challenge, $wasWarmUp, $matchContinuesOnNextChallenge, $restartChallenge);
	/**
	 * Method called on Round beginning
	 */
	function onBeginRound();
	/**
	 * Method called on Round ending
	 */
	function onEndRound();
	/**
	 * Method called when the server status change
	 * @param int StatusCode
	 * @param string StatsName
	*/
	function onStatusChanged($statusCode, $statusName);

	/**
	 * Method called when a player cross a checkPoint
	 * @param int $playerUid
	 * @param string $login
	 * @param int $timeOrScore
	 * @param int $curLap
	 * @param int $checkpointIndex
	*/
	function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex);
	/**
	 * Method called when a player finish a round
	 * @param int $playerUid
	 * @param string $login
	 * @param int $timeOrScore
	*/
	function onPlayerFinish($playerUid, $login, $timeOrScore);
	/**
	 * Method called when there is an incoherence with a player data
	 * @param int $playerUid
	 * @param string $login
	*/
	function onPlayerIncoherence($playerUid, $login); 
	/**
	 * Method called when a bill is updated
	 * @param int $billId
	 * @param int $state
	 * @param string $stateName
	 * @param int $transactionId
	*/
	function onBillUpdated($billId, $state, $stateName, $transactionId);
	/**
	 * Method called server receive data
	 * @param int $playerUid
	 * @param string $login
	 * @param base64 $data
	*/
	function onTunnelDataReceived($playerUid, $login, $data); 
	/**
	 * Method called when the challenge list is modified
	 * @param int $curChallengeIndex
	 * @param int $nextChallengeIndex
	 * @param bool $isListModified
	*/
	function onChallengeListModified($curChallengeIndex, $nextChallengeIndex, $isListModified); 
	/**
	 * Method called when player info changed
	 * @param SPlayerInfo $playerInfo
	*/
	function onPlayerInfoChanged($playerInfo);
	/**
	 * Method called when the Flow Control is manual
	 * @param string $transition
	*/
	function onManualFlowControlTransition($transition);
}

?>