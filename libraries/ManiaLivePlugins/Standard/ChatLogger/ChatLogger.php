<?php
/**
 * ChatLogger Plugin - Save everything typed in the chat in a file
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\Standard\ChatLogger;

use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;

class ChatLogger extends \ManiaLive\PluginHandler\Plugin
{
	function onInit()
	{
		$this->setVersion('1');
	}
	
	function onLoad()
	{
		$this->enableDedicatedEvents(ServerEvent::ON_PLAYER_CHAT);
	}
	
	function onPlayerChat($playerUid, $login, $text, $isRegistredCmd)
	{
		$player = $this->storage->getPlayerObject($login);
		
		if($player)
		{
			$this->writeLog('['.$player->login.':'.$player->nickName.'] '.$text);
		}
	}
}