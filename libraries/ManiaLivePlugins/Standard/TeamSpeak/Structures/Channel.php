<?php
/**
 * TeamSpeak Plugin - Connect to a TeamSpeak 3 server
 * Original work by refreshfr
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\Standard\TeamSpeak\Structures;

use ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak;

class Channel
{
	const PATH      = -3;
	const COMMENTS  = -2;
	const FREE_TALK = -1;
	const TEAM_1    = 0;
	const TEAM_2    = 1;
	
	static public $serverIds = array(
		self::PATH => 0,
		self::COMMENTS => 0,
		self::FREE_TALK => 0,
		self::TEAM_1 => 0,
		self::TEAM_2 => 0
	);
	static public $moveActions = array(
		self::COMMENTS => null,
		self::FREE_TALK => null,
		self::TEAM_1 => null,
		self::TEAM_2 => null
	);
	
	static private $byId = array();
	
	public $channelId;
	public $parentId = 0;
	public $name;
	public $joinPower = 0;
	public $talkPower = 0;
	
	function __construct($channelData, $permissionsList)
	{
		$this->channelId = (int) $channelData['cid'];
		if(isset(self::$byId[$this->channelId]))
			self::Erase($this->channelId);
		self::$byId[$this->channelId] = $this;
		
		if(isset($channelData['pid']))
			$this->parentId = (int) $channelData['pid'];
		else if(isset($channelData['cpid']))
			$this->parentId = (int) $channelData['cpid'];
		
		if(isset($channelData['channel_name']))
			$this->name = strval($channelData['channel_name']);
		
		if(isset($permissionsList['i_channel_needed_join_power']))
			$this->joinPower = (int) $permissionsList['i_channel_needed_join_power']['permvalue'];
		if(isset($permissionsList['i_client_needed_talk_power']))
			$this->talkPower = (int) $permissionsList['i_client_needed_talk_power']['permvalue'];
		
		$config = \ManiaLivePlugins\Standard\TeamSpeak\Config::getInstance();
		$actionHandler = \ManiaLive\Gui\ActionHandler::getInstance();
		$tsconnection = \ManiaLivePlugins\Standard\TeamSpeak\Connection::getInstance();
		if($config->serverChannelPath && $this->getPath() == $config->serverChannelPath)
			self::$serverIds[self::PATH] = $this->channelId;
		else if($this->parentId == self::$serverIds[self::PATH] && $this->name == $config->serverChannelName)
		{
			self::$serverIds[self::FREE_TALK] = $this->channelId;
			self::$moveActions[self::FREE_TALK] = $actionHandler->createAction(array($tsconnection, 'movePlayer'), $this->channelId);
			foreach(array(self::COMMENTS, self::TEAM_1, self::TEAM_2) as $const)
				if(self::$serverIds[$const])
				{
					self::$serverIds[$const] = 0;
					$actionHandler->deleteAction(self::$moveActions[$const]);
					self::$moveActions[$const] = null;
				}
		}
		else if(self::$serverIds[self::FREE_TALK] && $this->parentId == self::$serverIds[self::FREE_TALK])
		{
			if($this->joinPower == TeamSpeak::BASIC_POWER)
			{
				self::$serverIds[self::TEAM_1] = $this->channelId;
				self::$moveActions[self::TEAM_1] = $actionHandler->createAction(array($tsconnection, 'movePlayer'), $this->channelId);
			}
			else if($this->joinPower == TeamSpeak::BASIC_POWER+1)
			{
				self::$serverIds[self::TEAM_2] = $this->channelId;
				self::$moveActions[self::TEAM_2] = $actionHandler->createAction(array($tsconnection, 'movePlayer'), $this->channelId);
			}
			else if($this->talkPower == TeamSpeak::BASIC_POWER)
			{
				self::$serverIds[self::COMMENTS] = $this->channelId;
				self::$moveActions[self::COMMENTS] = $actionHandler->createAction(array($tsconnection, 'movePlayer'), $this->channelId);
			}
		}
	}
	
	function getPath()
	{
		$path = array();
		$channel = $this;
		do
		{
			array_unshift($path, $channel->name);
		} while($channel = self::GetById($channel->parentId));
		
		return implode('/', str_replace('/', '\\/', $path));
	}
	
	static function GetById($channelId)
	{
		if(isset(self::$byId[$channelId]))
			return self::$byId[$channelId];
		return null;
	}
	
	static function GetDefault()
	{
		return self::GetById(self::$serverIds[self::FREE_TALK]);
	}
	
	static function Erase($channelId)
	{
		if( ($channel = self::GetById($channelId)) )
		{
			unset(self::$byId[$channelId]);
			$const = array_search($channelId, self::$serverIds, true);
			if($const !== false)
			{
				self::$serverIds[$const] = 0;
				if($const != self::PATH)
				{
					\ManiaLive\Gui\ActionHandler::getInstance()->deleteAction(self::$moveActions[$const]);
					self::$moveActions[$const] = null;
				}
			}
		}
	}
	
	static function EraseAll()
	{
		self::$byId = array();
		foreach(self::$serverIds as &$serverId)
			$serverId = 0;
		foreach(self::$moveActions as &$moveAction)
			$moveAction = null;
	}
	
	static function IsClientAllowed($client, $channelId)
	{
		$player = \ManiaLive\Data\Storage::getInstance()->getPlayerObject($client->login);
		if($player && $player->teamId != -1)
			return $channelId == self::$serverIds[$player->teamId] || $channelId == self::$serverIds[self::PATH] || !in_array($channelId, self::$serverIds);
		return $channelId != self::$serverIds[self::TEAM_1] && $channelId != self::$serverIds[self::TEAM_2];
	}
}

?>