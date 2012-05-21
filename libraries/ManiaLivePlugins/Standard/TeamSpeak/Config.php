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

/**
 * @method \ManiaLivePlugins\Standard\TeamSpeak\Config getInstance()
 */
class Config extends \ManiaLib\Utils\Singleton
{
	public $host = '127.0.0.1';
	public $voicePort = '9987';
	public $password = '';
	public $queryPort = '10011';
	public $queryLogin = 'serveradmin';
	public $queryPassword = '';
	
	public $serverChannelPath = '';
	public $serverChannelName = '';
	public $commentators = array();
	
	function getConnectUrl($login)
	{
		$player = \ManiaLive\Data\Storage::getInstance()->getPlayerObject($login);
		$queryArgs = 'nickname='.rawurlencode(substr(\ManiaLib\Utils\Formatting::stripStyles($player->nickName), 0, 30));
		if( ($channel = Structures\Channel::GetDefault()) )
			$queryArgs .= '&channel='.rawurlencode($channel->getPath());
		$queryArgs .= '&token='.rawurlencode(Connection::getInstance()->getToken($login));
		if($this->password)
			$queryArgs .= '&password='.rawurlencode($this->password);
		
		return 'ts3server://'.$this->host.':'.$this->voicePort.'?'.$queryArgs;
	}
}

?>