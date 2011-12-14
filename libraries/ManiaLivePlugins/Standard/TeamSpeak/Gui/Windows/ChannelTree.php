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
use ManiaLivePlugins\Standard\TeamSpeak\Config;
use ManiaLivePlugins\Standard\TeamSpeak\Gui\Controls\Channel as ChannelUi;
use ManiaLivePlugins\Standard\TeamSpeak\Structures\Channel as ChannelStruct;
use ManiaLivePlugins\Standard\TeamSpeak\Structures\Client as ClientStruct;

/**
 * Description of ChannelTree
 */
class ChannelTree extends AbstractTree
{
	private static $channels = array();
	private static $parents = array();
	
	private $isAdmin;
	private $currentChannelId;
	
	static function Add(ChannelStruct $channel)
	{
		if(isset(self::$channels[$channel->channelId]))
			return;
		
		$channelUi = new ChannelUi($channel);
		
		self::$channels[$channel->channelId] = $channelUi;
		$parentUi = null;
		if( ($parent = $channel->getParent()) && isset(self::$channels[$parent->channelId]))
		{
			self::$parents[$channel->channelId] = $parent->channelId;
			$parentUi = self::$channels[$parent->channelId];
		}
		else
			self::$parents[$channel->channelId] = null;
		
		foreach(self::GetAll() as $channelTree)
			$channelTree->addElement($channelUi, $parentUi);
		
		self::RedrawAll();
	}
	
	static function Move(ChannelStruct $channel)
	{
		if(!isset(self::$channels[$channel->channelId]))
			return;
		
		$channelUi = self::$channels[$channel->channelId];
		$parentUi = null;
		if( ($parent = $channel->getParent()) && isset(self::$channels[$parent->channelId]))
		{
			self::$parents[$channel->channelId] = $parent->channelId;
			$parentUi = self::$channels[$parent->channelId];
		}
		else
			self::$parents[$channel->channelId] = null;
		
		foreach(self::GetAll() as $channelTree)
			$channelTree->moveElement($channelUi, $parentUi);
		
		self::RedrawAll();
	}
	
	static function Remove(ChannelStruct $channel)
	{
		if(!isset(self::$channels[$channel->channelId]))
			return;
		
		$channelUi = self::$channels[$channel->channelId];
		foreach(self::GetAll() as $channelTree)
			$channelTree->removeElement($channelUi);
		
		$channelUi->destroy();
		unset(self::$channels[$channel->channelId]);
		unset(self::$parents[$channel->channelId]);
		
		self::RedrawAll();
	}
	
	static function EraseAll()
	{
		parent::EraseAll();
		self::$channels = array();
		self::$parents = array();
	}
	
	protected function onConstruct()
	{
		parent::onConstruct();
		foreach(self::$parents as $channelId => $parentId)
			$this->addElement(self::$channels[$channelId], $parentId ? self::$channels[$parentId] : null);
		$this->isAdmin = AdminGroup::contains($this->getRecipient());
		$this->setNbElementsToShow(Config::getInstance()->nbChannelsToShow);
		$this->setBarBgcolor('0308');
	}
	
	function onDraw()
	{
		$client = ClientStruct::GetByLogin($this->getRecipient());
		$this->currentChannelId = $client != null ? $client->channelId : null;
		parent::onDraw();
	}
	
	protected function onShowElement($element, $nbParents)
	{
		$element->enableCommentButton($this->isAdmin);
		if($this->currentChannelId == $element->getChannel()->channelId)
		{
			$element->useNothing();
			$element->setBgcolor('0f88');
		}
		else
		{
			if($this->currentChannelId)
				$element->useAction();
			else
				$element->useUrl($this->getRecipient());
			$b = max(0, $nbParents - 4);
			$g = dechex(min(15, 4 + 4 * $nbParents));
			$a = dechex(max(4, 8 - $nbParents));
			$element->setBgcolor('0'.$g.$b.$a);
		}
	}
}

?>