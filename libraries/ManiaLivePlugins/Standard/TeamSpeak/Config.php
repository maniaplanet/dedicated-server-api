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

namespace ManiaLivePlugins\Standard\TeamSpeak;

use ManiaLib\Utils\Formatting;

/**
 * @method \ManiaLivePlugins\Standard\TeamSpeak\Config getInstance()
 */
class Config extends \ManiaLib\Utils\Singleton
{
	public $ipAddress = '127.0.0.1';
    public $voicePort = '9987';
    public $password = '';
    public $queryPort = '10011';
    public $queryLogin = 'serveradmin';
    public $queryPassword = '';
	
	public $listAllChannels = false;
	public $useDedicatedChannel = true;
	public $useLangChannels = true;
	public $dedicatedChannelName = '';
	public $commentators = array();
	
	public $nbChannelsToShow = 15;
	public $nbClientsToShow = 10;
	
	function getConnectUrl($channel, $login)
	{
		$player = \ManiaLive\Data\Storage::getInstance()->getPlayerObject($login);
		$nickname = substr(Formatting::stripStyles($player->nickName), 0, 27 - strlen($login)).' ('.$login.')';
		
		$queryArgs = 'nickname='.rawurlencode($nickname);
		if($channel || ($channel = $this->getPlayerDefaultChannel($player)) )
			$queryArgs .= '&channel='.rawurlencode($channel->serverPath);
		if($this->password)
			$queryArgs .= '&password='.rawurlencode($this->password);
		
		return 'ts3server://'.$this->ipAddress.':'.$this->voicePort.'?'.$queryArgs;
	}
	
	function getPlayerDefaultChannel($player)
	{
		if($this->useLangChannels)
		{
			$channel = Structures\Channel::GetByPath($this->dedicatedChannelName.'/'.Connection::$languages[substr($player->language, 0, 2)]);
			if($channel)
				return $channel;
		}
		if($this->useDedicatedChannel)
		{
			$channel = Structures\Channel::GetByPath($this->dedicatedChannelName);
			if($channel)
				return $channel;
		}
		return null;
	}
}

?>