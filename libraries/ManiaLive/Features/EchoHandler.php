<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLive\Features;

use DedicatedApi\Connection;
use ManiaLive\DedicatedApi\Callback\Event;
use ManiaLive\DedicatedApi\Callback\Listener;
use ManiaLive\DedicatedApi\Config;
use ManiaLive\Event\Dispatcher;

class EchoHandler extends \ManiaLib\Utils\Singleton implements Listener
{
	/** @var Connection */
	private $connection;
	/** @var string */
	private $identifier;
	
	protected function __construct()
	{
		$this->identifier = 'ManiaLive '.\ManiaLiveApplication\Version;
		$config = Config::getInstance();
		$this->connection = Connection::factory(
				$config->host,
				$config->port,
				$config->timeout,
				$config->user,
				$config->password
			);
		Dispatcher::register(Event::getClass(), $this, Event::ON_ECHO);
	}
	
	public function onEcho($internal, $public)
	{
		$call = explode(':', substr($internal, 1), 2);
		if(isset($call[1]) && $call[1] != $this->identifier)
			return;
		
		if(substr($internal, 0, 1) == '?')
		{
			switch($call[0])
			{
				case 'census':
					$this->connection->dedicatedEcho($this->identifier, '!census:'.$public);
					break;
				case 'stop':
					\ManiaLiveApplication\Application::getInstance()->kill();
					break;
			}
		}
	}
	
	public function onBeginMap($map, $warmUp, $matchContinuation) {}
	public function onBeginMatch() {}
	public function onBeginRound() {}
	public function onBillUpdated($billId, $state, $stateName, $transactionId) {}
	public function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap) {}
	public function onEndMatch($rankings, $winnerTeamOrMap) {}
	public function onEndRound() {}
	public function onManualFlowControlTransition($transition) {}
	public function onMapListModified($curMapIndex, $nextMapIndex, $isListModified) {}
	public function onModeScriptCallback($param1, $param2) {}
	public function onPlayerChat($playerUid, $login, $text, $isRegistredCmd) {}
	public function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex) {}
	public function onPlayerConnect($login, $isSpectator) {}
	public function onPlayerDisconnect($login, $disconnectionReason) {}
	public function onPlayerFinish($playerUid, $login, $timeOrScore) {}
	public function onPlayerIncoherence($playerUid, $login) {}
	public function onPlayerInfoChanged($playerInfo) {}
	public function onPlayerManialinkPageAnswer($playerUid, $login, $answer, array $entries) {}
	public function onServerStart() {}
	public function onServerStop() {}
	public function onStatusChanged($statusCode, $statusName) {}
	public function onTunnelDataReceived($playerUid, $login, $data) {}
	public function onVoteUpdated($stateName, $login, $cmdName, $cmdParam) {}
	public function onPlayerAlliesChanged($login) {}
}

?>
