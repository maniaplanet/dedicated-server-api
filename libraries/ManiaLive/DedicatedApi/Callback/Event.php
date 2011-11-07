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

class Event extends \ManiaLive\Event\Event
{
	const ON_PLAYER_CONNECT                 = 0x1;
	const ON_PLAYER_DISCONNECT              = 0x2;
	const ON_PLAYER_CHAT                    = 0x4;
	const ON_PLAYER_MANIALINK_PAGE_ANSWER   = 0x8;
	const ON_ECHO                           = 0x10;
	const ON_SERVER_START                   = 0x20;
	const ON_SERVER_STOP                    = 0x40;
	const ON_BEGIN_MATCH                    = 0x80;
	const ON_END_MATCH                      = 0x100;
	const ON_BEGIN_MAP                      = 0x200;
	const ON_END_MAP                        = 0x400;
	const ON_BEGIN_ROUND                    = 0x800;
	const ON_END_ROUND                      = 0x1000;
	const ON_STATUS_CHANGED                 = 0x2000;
	const ON_PLAYER_CHECKPOINT              = 0x4000;
	const ON_PLAYER_FINISH                  = 0x8000;
	const ON_PLAYER_INCOHERENCE             = 0x10000;
	const ON_BILL_UPDATED                   = 0x20000;
	const ON_TUNNEL_DATA_RECEIVED           = 0x40000;
	const ON_MAP_LIST_MODIFIED              = 0x80000;
	const ON_PLAYER_INFO_CHANGED            = 0x100000;
	const ON_MANUAL_FLOW_CONTROL_TRANSITION = 0x200000;
	const ON_VOTE_UPDATED                   = 0x400000;
	const ON_RULES_SCRIPT_CALLBACK          = 0x800000;
	
	static private $rc;
	protected $params;
	
	function __construct($method, $params = array())
	{
		parent::__construct(self::getOnWhat($method));
		$this->params = $params;
	}
	
	function fireDo($listener)
	{
		$p = $this->params;
		// Explicit calls are always *a lot* faster than using call_user_func() even if longer to write
		switch($this->onWhat)
		{
			case self::ON_PLAYER_CONNECT: $listener->onPlayerConnect($p[0], $p[1]); break;
			case self::ON_PLAYER_DISCONNECT: $listener->onPlayerDisconnect($p[0]); break;
			case self::ON_PLAYER_CHAT: $listener->onPlayerChat($p[0], $p[1], $p[2], $p[3]); break;
			case self::ON_PLAYER_MANIALINK_PAGE_ANSWER: $listener->onPlayerManialinkPageAnswer($p[0], $p[1], $p[2], $p[3]); break;
			case self::ON_ECHO: $listener->onEcho($p[0], $p[1]); break;
			case self::ON_SERVER_START: $listener->onServerStart(); break;
			case self::ON_SERVER_STOP: $listener->onServerStop(); break;
			case self::ON_BEGIN_MATCH: $listener->onBeginMatch($p[0]); break;
			case self::ON_END_MATCH: $listener->onEndMatch($p[0], $p[1]); break;
			case self::ON_BEGIN_MAP: $listener->onBeginMap($p[0], $p[1], $p[2]); break;
			case self::ON_END_MAP: $listener->onEndMap($p[0], $p[1], $p[2], $p[3], $p[4]); break;
			case self::ON_BEGIN_ROUND: $listener->onBeginRound(); break;
			case self::ON_END_ROUND: $listener->onEndRound(); break;
			case self::ON_STATUS_CHANGED: $listener->onStatusChanged($p[0], $p[1]); break;
			case self::ON_PLAYER_CHECKPOINT: $listener->onPlayerCheckpoint($p[0], $p[1], $p[2], $p[3], $p[4]); break;
			case self::ON_PLAYER_FINISH: $listener->onPlayerFinish($p[0], $p[1], $p[2]); break;
			case self::ON_PLAYER_INCOHERENCE: $listener->onPlayerIncoherence($p[0], $p[1]);  break;
			case self::ON_BILL_UPDATED: $listener->onBillUpdated($p[0], $p[1], $p[2], $p[3]); break;
			case self::ON_TUNNEL_DATA_RECEIVED: $listener->onTunnelDataReceived($p[0], $p[1], $p[2]);  break;
			case self::ON_MAP_LIST_MODIFIED: $listener->onMapListModified($p[0], $p[1], $p[2]);  break;
			case self::ON_PLAYER_INFO_CHANGED: $listener->onPlayerInfoChanged($p[0]); break;
			case self::ON_MANUAL_FLOW_CONTROL_TRANSITION: $listener->onManualFlowControlTransition($p[0]); break;
			case self::ON_VOTE_UPDATED: $listener->onVoteUpdated($p[0], $p[1], $p[2], $p[3]); break;
			case self::ON_RULES_SCRIPT_CALLBACK: $listener->onRulesScriptCallback($p[0], $p[1]); break;
		}
	}
	
	private static function getOnWhat($method)
	{
		$constName = 'ON_'.strtoupper(preg_replace('/([a-z])([A-Z])/', '$1_$2', $method));
		
		if(!self::$rc)
			self::$rc = new \ReflectionClass(get_called_class());
		
		return self::$rc->getConstant($constName);
	}
}

?>