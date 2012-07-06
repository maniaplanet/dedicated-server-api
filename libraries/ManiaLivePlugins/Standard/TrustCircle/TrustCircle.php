<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 7580 $:
 * @author      $Author: gwendal $:
 * @date        $Date: 2012-06-27 11:56:13 +0200 (mer., 27 juin 2012) $:
 */

namespace ManiaLivePlugins\Standard\TrustCircle;

use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;
use ManiaLive\Features\Admin\AdminGroup;
use Maniaplanet\WebServices\TrustCircles;

class TrustCircle extends \ManiaLive\PluginHandler\Plugin
{
	private $wsClient;
	private $whiteList = array();
	private $preparedRules = array();
	
	function onInit()
	{
		$this->setVersion('1.0b');
	}
	
	function onLoad()
	{
		$wsConfig = \ManiaLive\Features\WebServices\Config::getInstance();
		$this->wsClient = new TrustCircles($wsConfig->username, $wsConfig->password);

		if($wsConfig->username && $wsConfig->password)
		{
			$admins = AdminGroup::get();
			$this->registerChatCommand('+black', 'addToBlackList', 1, true, $admins);
			$this->registerChatCommand('+white', 'addToWhiteList', 1, true, $admins);
			$this->registerChatCommand('-black', 'removeFromBlackList', 1, true, $admins);
			$this->registerChatCommand('-white', 'removeFromWhiteList', 1, true, $admins);
			
			$currentBlackList = array_map(function($player) { return $player->login; }, $this->connection->getBlackList(-1, 0));
			$distantBlackList = $this->wsClient->getBlackList();
			foreach(array_diff($distantBlackList, $currentBlackList) as $player)
				$this->connection->blackList($player);
			foreach(array_diff($currentBlackList, $distantBlackList) as $player)
				$this->connection->unBlackList($player);
			
			$this->whiteList = $this->wsClient->getWhiteList();
		}
		
		foreach((array) Config::getInstance()->rules as $rule)
			$this->preparedRules[] = Rule::Prepare($rule);
		
		foreach($this->storage->players as $player)
			$this->onPlayerConnect($player->login, false);
		foreach($this->storage->spectators as $player)
			$this->onPlayerConnect($player->login, true);
		
		$this->enableDedicatedEvents(ServerEvent::ON_PLAYER_CONNECT | ServerEvent::ON_PLAYER_DISCONNECT);
	}
	
	function addToBlackList($caller, $login)
	{
		try
		{
			$this->connection->blackList($login);
			$this->connection->kick($login);
		}
		catch(\Exception $e) {}
		$this->wsClient->blackList($login);
		if($caller)
			$this->connection->chatSendServerMessage('Login `'.$login.'` has been successfully blacklisted', $caller);
	}
	
	function addToWhiteList($caller, $login)
	{
		$this->wsClient->whiteList($login);
		if($caller)
			$this->connection->chatSendServerMessage('Login `'.$login.'` has been successfully whitelisted', $caller);
	}
	
	function removeFromBlackList($caller, $login)
	{
		try
		{
			$this->connection->unBlackList($login);
		}
		catch(\Exception $e) {}
		$this->wsClient->unBlackList($login);
		if($caller)
			$this->connection->chatSendServerMessage('Login `'.$login.'` has been successfully removed from blacklist', $caller);
	}
	
	function removeFromWhiteList($caller, $login)
	{
		$this->wsClient->unWhiteList($login);
		if($caller)
			$this->connection->chatSendServerMessage('Login `'.$login.'` has been successfully removed from whitelist', $caller);
	}
	
	function onPlayerConnect($login, $isSpectator)
	{
		if(AdminGroup::contains($login) || in_array($login, $this->whiteList))
			return;
		
		$blacks = $whites = 0;
		foreach((array) Config::getInstance()->readFrom as $circle)
		{
			$karma = $this->wsClient->getKarma($circle, $login);
			$blacks += $karma->blacks;
			$whites += $karma->whites;
		}
		
		foreach($this->preparedRules as $rule)
			switch($rule->check($blacks, $whites))
			{
				case -2:
					$this->addToBlackList(null, $login);
					return;
				case -1:
					$this->connection->kick($login, 'You\'re not a trusted player');
				case 1:
					return;
				case 2:
					$this->addToWhiteList(null, $login);
					return;
			}
	}
}

?>
