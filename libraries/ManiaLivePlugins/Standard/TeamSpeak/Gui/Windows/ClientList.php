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

namespace ManiaLivePlugins\Standard\TeamSpeak\Gui\Windows;

use ManiaLive\Features\Admin\AdminGroup;
use ManiaLive\Gui\Group;
use ManiaLivePlugins\Standard\TeamSpeak\Config;
use ManiaLivePlugins\Standard\TeamSpeak\Structures\Channel as ChannelStruct;
use ManiaLivePlugins\Standard\TeamSpeak\Gui\Controls\Client as ClientUi;
use ManiaLivePlugins\Standard\TeamSpeak\Structures\Client as ClientStruct;

/**
 * Description of ClientList
 */
class ClientList extends AbstractTree
{
	private static $clientsByChannel = array(null => array());
	private static $channels = array();
	
	private $isAdmin;
	
	static function Add(ClientStruct $client)
	{
		if(isset(self::$clientsByChannel[null][$client->clientId]))
			return;
		
		$clientUi = new ClientUi($client);
		
		if(!isset(self::$clientsByChannel[$client->channelId]))
			self::$clientsByChannel[$client->channelId] = array();
		self::$clientsByChannel[$client->channelId][$client->clientId] = $clientUi;
		self::$clientsByChannel[null][$client->clientId] = $clientUi;
		self::$channels[$client->clientId] = $client->channelId;
		
		foreach(ChannelStruct::Get($client->channelId)->clients as $channelClient)
			foreach(self::Get($channelClient->login) as $clientList)
			{
				$clientList->addElement($clientUi);
				$clientList->redraw();
			}
		foreach(self::Get(Group::Get('admin')) as $clientList)
		{
			$clientList->addElement($clientUi);
			$clientList->redraw();
		}
	}
	
	static function Remove(ClientStruct $client)
	{
		if(!isset(self::$clientsByChannel[null][$client->clientId]))
			return;
		
		$clientUi = self::$clientsByChannel[$client->channelId][$client->clientId];
		
		foreach(ChannelStruct::Get($client->channelId)->clients as $channelClient)
			foreach(self::Get($channelClient->login) as $clientList)
			{
				$clientList->removeElement($clientUi);
				$clientList->redraw();
			}
		foreach(self::Get(Group::Get('admin')) as $clientList)
		{
			$clientList->removeElement($clientUi);
			$clientList->redraw();
		}
		
		$clientUi->destroy();
		unset(self::$clientsByChannel[$client->channelId][$client->clientId]);
		unset(self::$clientsByChannel[null][$client->clientId]);
		unset(self::$channels[$client->clientId]);
	}
	
	static function EraseAll()
	{
		parent::EraseAll();
		self::$clientsByChannel = array(null => array());
	}
	
	protected function onConstruct($channelId=null)
	{
		parent::onConstruct();
		foreach(self::$clientsByChannel[$channelId] as $clientUi)
			$this->addElement($clientUi);
		$this->isAdmin = AdminGroup::contains($this->getRecipient()) || $this->getRecipient() === Group::Get('admin');
		$this->setNbElementsToShow(Config::getInstance()->nbClientsToShow);
		$this->setBarBgcolor('0038');
	}
	
	protected function onShowElement($element, $nbParents)
	{
		$element->enableCommentButton($this->isAdmin);
	}
}

?>