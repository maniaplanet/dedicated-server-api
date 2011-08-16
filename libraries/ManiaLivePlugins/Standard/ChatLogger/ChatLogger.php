<?php

namespace ManiaLivePlugins\Standard\ChatLogger;

use ManiaLive\Utilities\Logger;

class ChatLogger extends \ManiaLive\PluginHandler\Plugin
{
	function onLoad()
	{
		$this->enableDedicatedEvents();
	}
	
	function onPlayerChat($playerUid, $login, $text, $isRegistredCmd)
	{
		$player = $this->storage->getPlayerObject($login);
		
		if($player)
		{
			$log = Logger::getLog(Config::getInstance()->logFilename);
			$log->write('['.date('M d H:i:s').']['.$player->login.':'.$player->nickName.'] '.$text.APP_NL);
		}
	}
}