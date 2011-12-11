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

namespace ManiaLive\Gui;

use ManiaLive\Event\Dispatcher;
use ManiaLive\DedicatedApi\Callback\Listener as ServerListener;
use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;

/**
 * Description of ActionHandler
 */
final class ActionHandler extends \ManiaLib\Utils\Singleton implements ServerListener
{
	const NONE = 0xFFFFFFFF;
	
	protected $callbacks = array();
	protected $lastAction = 0;
	
	protected function __construct()
	{
		Dispatcher::register(ServerEvent::getClass(), $this, ServerEvent::ON_PLAYER_MANIALINK_PAGE_ANSWER);
	}
	
	function createAction($callback)
	{
		if(!is_array($callback) || !is_callable($callback))
			throw new \InvalidArgumentException('Invalid callback');
		
		$args = func_get_args();
		array_shift($args);
		$callback = array($callback, $args);
		
		$action = array_search($callback, $this->callbacks, true);
		if($action !== false)
			return $action;
		
		$this->callbacks[++$this->lastAction] = $callback;
		return $this->lastAction;
	}
	
	// TODO this should be done automatically but PHP has no refcount function
	// nor weak references yet... so please don't forget to call this method
	// to avoid memory leaks !!!!
	function deleteAction($action)
	{
		unset($this->callbacks[$action]);
	}
	
	function onPlayerManialinkPageAnswer($playerUid, $login, $answer, array $entries)
	{
		if(isset($this->callbacks[$answer]))
		{
			$params = array($login);
			array_splice($params, count($params), 0, $this->callbacks[$answer][1]);
			if(count($entries))
			{
				$entryValues = array();
				foreach($entries as $entry)
					$entryValues[$entry['Name']] = $entry['Value'];
				$params[] = $entryValues;
			}
			call_user_func_array($this->callbacks[$answer][0], $params);
		}
	}
	
	function onPlayerConnect($login, $isSpectator) {}
	function onPlayerDisconnect($login) {}
	function onPlayerChat($playerUid, $login, $text, $isRegistredCmd) {}
	function onEcho($internal, $public) {}
	function onServerStart() {}
	function onServerStop() {}
	function onBeginMatch($map) {}
	function onEndMatch($rankings, $map) {}
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
	function onRulesScriptCallback($param1, $param2) {}
}

?>