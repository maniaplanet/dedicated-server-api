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

use ManiaLivePlugins\Standard\TeamSpeak\Config;
use ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak;

class Client
{
	private static $byId = array();
	private static $byLogin = array();
	
	public $clientId;
	public $databaseId;
	public $channelId;
	public $isCommentator;
	public $login;
	
	function __construct($clientData, $customData)
	{
		$this->clientId = (int) $clientData['clid'];
		$this->databaseId = (int) $clientData['client_database_id'];
		self::$byId[$this->clientId] = $this;
		
		if(isset($clientData['cid']))
			$this->channelId = (int) $clientData['cid'];
		else if(isset($clientData['ctid']))
			$this->channelId = (int) $clientData['ctid'];
		
		if(isset($clientData['client_talk_power']))
			$this->isCommentator = $clientData['client_talk_power'] == TeamSpeak::BASIC_POWER;
		
		if(isset($customData['maniaplanet_login']))
		{
			$this->login = $customData['maniaplanet_login'];
			$this->isCommentator = $this->isCommentator || in_array($this->login, Config::getInstance()->commentators);
			self::$byLogin[$this->login] = $this;
		}
	}
	
	static function GetById($clientId)
	{
		if(isset(self::$byId[$clientId]))
			return self::$byId[$clientId];
		return null;
	}
	
	static function GetByLogin($login)
	{
		if(isset(self::$byLogin[$login]))
			return self::$byLogin[$login];
		return null;
	}
	
	static function EraseById($clientId)
	{
		if( ($client = self::GetById($clientId)) )
		{
			unset(self::$byId[$clientId]);
			if($client->login)
				unset(self::$byLogin[$client->login]);
		}
	}
	
	static function EraseByLogin($login)
	{
		if( ($client = self::GetByLogin($login)) )
		{
			unset(self::$byLogin[$login]);
			if($client->login)
				unset(self::$byId[$client->clientId]);
		}
	}
	
	static function EraseAll()
	{
		self::$byId = array();
		self::$byLogin = array();
	}
}

?>