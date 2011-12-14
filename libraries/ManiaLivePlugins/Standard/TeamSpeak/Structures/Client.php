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

use ManiaLive\Data\Storage;

/**
 * Description of Client
 */
class Client extends Observable
{
	static private $instances = array();
	static private $instancesByLogin = array();
	
	public $clientId;
	public $databaseId;
	public $channelId;
	public $nickname;
	public $isAway;
	
	public $login = null;
	public $nicknameToShow = null;
	public $isCommentator = false;
	
	static function CreateFromTeamSpeak($clientInfo)
	{
		$clientId = (int) $clientInfo['clid'];
		$client = new self();
		$client->clientId = $clientId;
		$client->databaseId = (int) $clientInfo['client_database_id'];
		$client->update($clientInfo);

		$matches = array();
		if(preg_match('/ \(([^)]+)\)$/u', $client->nickname, $matches))
		{
			$client->login = $matches[1];
			if( ($player = Storage::getInstance()->getPlayerObject($matches[1])) )
				$client->nicknameToShow = $player->nickName;
			else
				$client->nicknameToShow = $client->nickname;
			self::$instancesByLogin[$client->login] = $client;
		}
		else
			$client->nicknameToShow = $client->nickname;
		if(isset($clientInfo['client_is_talker']))
			$client->isCommentator = (bool) $clientInfo['client_is_talker'];

		self::$instances[$clientId] = $client;
		return $client;
	}
	
	static function Get($clientId)
	{
		if(isset(self::$instances[$clientId]))
			return self::$instances[$clientId];
		return null;
	}
	
	static function GetByLogin($login)
	{
		if(isset(self::$instancesByLogin[$login]))
			return self::$instancesByLogin[$login];
		return null;
	}
	
	static function GetAll()
	{
		return self::$instances;
	}
	
	static function Erase($clientId)
	{
		if( ($client = self::Get($clientId)) )
		{
			$client->removeAllObservers();
			if( ($channel = Channel::Get($client->channelId)) )
			{
				unset($channel->clients[$clientId]);
				$channel->notifyObservers();
			}
			unset(self::$instances[$clientId]);
			if($client->login)
				unset(self::$instancesByLogin[$client->login]);
		}
	}
	
	static function EraseAll()
	{
		foreach(self::$instances as $client)
		{
			$client->removeAllObservers();
			if( ($channel = Channel::Get($client->channelId)) )
				unset($channel->clients[$client->clientId]);
		}
		self::$instances = array();
		self::$instancesByLogin = array();
	}
	
	private function __construct() {}
	
	function update($clientInfo)
	{
		if(isset($clientInfo['client_nickname']))
			$this->nickname = $clientInfo['client_nickname'];
		if(isset($clientInfo['client_away']))
			$this->isAway = (bool) $clientInfo['client_away'];
		
		$newChannel = $this->channelId;
		if(isset($clientInfo['cid']))
			$newChannel = (int) $clientInfo['cid'];
		else if(isset($clientInfo['ctid']))
			$newChannel = (int) $clientInfo['ctid'];
		
		if($newChannel !== $this->channelId)
		{
			if( ($channel = Channel::Get($this->channelId)) )
			{
				unset($channel->clients[$this->clientId]);
				$channel->notifyObservers();
			}
			$this->channelId = $newChannel;
			if( ($channel = Channel::Get($this->channelId)) )
			{
				$channel->clients[$this->clientId] = $this;
				$channel->notifyObservers();
			}
		}
		
		$this->notifyObservers();
	}
}

?>