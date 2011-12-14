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

/**
 * Description of Channel
 */
class Channel extends Observable
{
	static private $instances = array();
	static private $instancesByName = array();
	static private $defaultChannelId = 0;
	
	public $channelId;
	public $parentId = 0;
	public $name;
	
	public $serverPath;
	public $hasToBeListed;
	public $commentatorEnabled = false;
	
	public $subChannels = array();
	public $clients = array();
	
	static function CreateFromTeamSpeak($channelInfo)
	{
		$channelId = (int) $channelInfo['cid'];
		$channel = new self();
		$channel->channelId = $channelId;
		$channel->update($channelInfo);

		self::$instances[$channelId] = $channel;
		self::$instancesByName[$channel->name] = $channel;
		return $channel;
	}
	
	static function Get($channelId)
	{
		if(isset(self::$instances[$channelId]))
			return self::$instances[$channelId];
		return null;
	}
	
	static function GetByName($channelName)
	{
		if(isset(self::$instancesByName[$channelName]))
			return self::$instancesByName[$channelName];
		return null;
	}
	
	static function GetDefault()
	{
		return self::Get(self::$defaultChannelId);
	}
	
	static function GetAll()
	{
		return self::$instances;
	}
	
	static function Erase($channelId)
	{
		if( ($channel = self::Get($channelId)) )
		{
			$channel->removeAllObservers();
			if( ($parentChannel = Channel::Get($channel->parentId)) )
				unset($parentChannel->subChannels[$channelId]);
			unset(self::$instances[$channelId]);
			unset(self::$instances[$channel->name]);
			if($channelId == self::$defaultChannelId)
				self::$defaultChannelId = 0;
		}
	}
	
	static function EraseAll()
	{
		foreach(self::$instances as $channel)
		{
			$channel->removeAllObservers();
			if( ($parentChannel = Channel::Get($channel->parentId)) )
				unset($parentChannel->subChannels[$channel->channelId]);
		}
		self::$instances = array();
		self::$instancesByName = array();
	}
	
	private function __construct() {}
	
	function update($channelInfo)
	{
		if(isset($channelInfo['channel_name']))
			$this->name = strval($channelInfo['channel_name']);
		if(isset($channelInfo['channel_needed_talk_power']))
			$this->commentatorEnabled = $channelInfo['channel_needed_talk_power'] >= \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak::COMMENTATOR;

		if( ($parentChannel = Channel::Get($this->parentId)) )
			unset($parentChannel->subChannels[$this->channelId]);
		
		if(isset($channelInfo['pid']))
			$this->parentId = (int) $channelInfo['pid'];
		else if(isset($channelInfo['cpid']))
			$this->parentId = (int) $channelInfo['cpid'];
		
		if( ($parentChannel = self::Get($this->parentId)) )
		{
			$parentChannel->subChannels[$this->channelId] = $this;
			$this->serverPath = $parentChannel->serverPath.'/'.str_replace('/', '\\/', $this->name);
		}
		else
			$this->serverPath = str_replace('/', '\\/', $this->name);
		
		$config = Config::getInstance();
		if(!$this->parentId && $this->name == $config->dedicatedChannelName)
			self::$defaultChannelId = $this->channelId;
		
		$this->hasToBeListed = 
				$config->listAllChannels
				|| (!$config->useLangChannels && $this->channelId == self::$defaultChannelId)
				|| ($config->useLangChannels && $parentChannel && $parentChannel->channelId == self::$defaultChannelId)
				|| ($parentChannel && $parentChannel->hasToBeListed);
		
		$this->notifyObservers();
	}
	
	function getParent()
	{
		return self::Get($this->parentId);
	}
}

?>