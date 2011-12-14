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

use ManiaLib\Gui\Elements\BgsPlayerCard;
use ManiaLib\Gui\Elements\Icons64x64_1;
use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\Quad;
use ManiaLib\Gui\Elements\UIConstructionSimple_Buttons;
use ManiaLib\Gui\Layouts\Column;
use ManiaLib\Gui\Layouts\Flow;
use ManiaLive\Features\Admin\AdminGroup;
use ManiaLive\Gui\Group;
use ManiaLive\Gui\Controls\Frame;
use ManiaLivePlugins\Standard\TeamSpeak\Config;
use ManiaLivePlugins\Standard\TeamSpeak\Connection as TSConnection;
use ManiaLivePlugins\Standard\TeamSpeak\Gui\Images as TSImages;
use ManiaLivePlugins\Standard\TeamSpeak\Gui\Controls\MainButton;
use ManiaLivePlugins\Standard\TeamSpeak\Gui\Windows\ChannelTree;
use ManiaLivePlugins\Standard\TeamSpeak\Gui\Windows\ClientList;
use ManiaLivePlugins\Standard\TeamSpeak\Structures\Channel;

/**
 * Description of Main
 */
class Main extends \ManiaLive\Gui\Window
{
	const MAIN        = 0;
	const CURRENT     = 1;
	const ADMIN       = 2;
	const CHANNELS    = 3;
	const COMMENT_ON  = 4;
	const COMMENT_OFF = 5;
	
	private $logo;
	private $status;
	private $mainFrame;
	private $commentFrame;
	private $infoButton;
	
	private $buttons = array();
	private $visibilities = array(self::MAIN => true);
	private $recipientIsAdmin = false;
	
	protected function onConstruct()
	{
		$this->recipientIsAdmin = AdminGroup::contains($this->getRecipient());
		
		$layout = new Flow();
		$layout->setBorder(.1, .1);
		$layout->setMargin(.2, .2);
		$this->mainFrame = new Frame(0, 0, $layout);
		$this->addComponent($this->mainFrame);
		
		$this->logo = new MainButton();
		$this->logo->setBgcolorFocus('8888');
		$this->logo->setAction($this->createAction(array($this, 'toggleMain')));
		$this->logo->showIcon();
		$this->mainFrame->addComponent($this->logo);
		
		$this->status = new MainButton();
		$this->status->setBgcolorFocus('8888');
		$this->status->showText();
		$this->status->enableAutonewline();
		$this->mainFrame->addComponent($this->status);
		
		if($this->recipientIsAdmin)
		{
			$layout = new Column();
			$layout->setMarginHeight(.2);
			$this->commentFrame = new Frame(0, 0, $layout);
			$this->mainFrame->addComponent($this->commentFrame);
			
			$button = new MainButton();
			$button->setIconImage(TSImages::getInstance()->channelCommentOn);
			$button->setBgcolorFocus('8888');
			$button->setAction($this->createAction(array(TSConnection::getInstance(), 'toggleGlobalComment'), true));
			$button->showIcon();
			$this->buttons[self::COMMENT_ON] = $button;
			$this->commentFrame->addComponent($button);
			
			$button = new MainButton();
			$button->setIconImage(TSImages::getInstance()->channelCommentOff);
			$button->setBgcolorFocus('8888');
			$button->setAction($this->createAction(array(TSConnection::getInstance(), 'toggleGlobalComment'), false));
			$button->showIcon();
			$this->buttons[self::COMMENT_OFF] = $button;
			$this->commentFrame->addComponent($button);
		}
		
		
		$button = new MainButton();
		$button->setIconStyle(Icons64x64_1::Icons64x64_1, Icons64x64_1::ShowUp2);
		$button->setBgcolorFocus('12a8');
		$button->showIcon();
		$button->setText('Connect');
		$button->showText();
		$this->buttons[self::CURRENT] = $button;
		$this->visibilities[self::CURRENT] = false;
		$this->mainFrame->addComponent($button);
		
		if($this->recipientIsAdmin)
		{
			$button = new MainButton();
			$button->setIconStyle(Icons64x64_1::Icons64x64_1, Icons64x64_1::ShowUp2);
			$button->setBgcolorFocus('12a8');
			$button->showIcon();
			$button->setText('All clients');
			$button->showText();
			$button->setAction($this->createAction(array($this, 'toggleAdminClientList')));
			$this->buttons[self::ADMIN] = $button;
			$this->visibilities[self::ADMIN] = false;
			$this->mainFrame->addComponent($button);
		}
		
		$button = new MainButton();
		$button->setIconStyle(Icons64x64_1::Icons64x64_1, Icons64x64_1::ShowLeft2);
		$button->setBgcolorFocus('1a28');
		$button->showIcon();
		$button->setText('Channels');
		$button->showText();
		$button->setAction($this->createAction(array($this, 'toggleChannelTree')));
		$this->buttons[self::CHANNELS] = $button;
		$this->visibilities[self::CHANNELS] = false;
		$this->mainFrame->addComponent($button);
		
		$this->infoButton = new UIConstructionSimple_Buttons(4);
		$this->infoButton->setSubStyle(UIConstructionSimple_Buttons::Help);
		$this->infoButton->setAction($this->createAction(array($this, 'showLegend')));
		$this->infoButton->setAlign('right', 'bottom');
		$this->addComponent($this->infoButton);
		
		$this->setSize(60, 15);
		$this->setPosition(-160, 75);
	}
	
	function setNotConnected()
	{
		$this->logo->setIconImage(TSImages::getInstance()->tsGrey);
		$this->status->setText('Connect to the local TeamSpeak server. Choose your channel or join a default one.');
		foreach($this->buttons as $button)
			$button->enableLinks();
		$this->buttons[self::CURRENT]->setUrl(Config::getInstance()->getConnectUrl(null, $this->getRecipient()));
	}
	
	function setConnected()
	{
		$this->logo->setIconImage(TSImages::getInstance()->tsGreen);
		$this->status->setText('You\'re currently connected');
		foreach($this->buttons as $button)
			$button->enableLinks();
		$this->buttons[self::CURRENT]->setAction($this->createAction(array($this, 'toggleClientList')));
	}
	
	function setError()
	{
		$this->logo->setIconImage(TSImages::getInstance()->tsRed);
		$this->status->setText('Connection problem between ManiaLive and TeamSpeak.');
		foreach($this->buttons as $button)
			$button->disableLinks();
		$login = $this->getRecipient();
		if($this->visibilities[self::CURRENT])
			$this->hideClientList($login);
		if($this->recipientIsAdmin && $this->visibilities[self::ADMIN])
			$this->hideAdminClientList($login);
		if($this->visibilities[self::CHANNELS])
			$this->hideChannelTree($login);
	}
	
	function setDefaultButtonText($text)
	{
		$this->buttons[self::CURRENT]->setText($text);
	}
	
	function showLegend($login)
	{
		Legend::Create()->showModal($login);
	}
	
	function showClientList($login)
	{
		$clientList = ClientList::Create($login);
		$clientList->setPosition($this->posX + .1, $this->posY - $this->sizeY - .6);
		$clientList->setSize($this->sizeX * .8);
		$clientList->show();
		$this->visibilities[self::CURRENT] = true;
		$this->buttons[self::CURRENT]->setIconStyle(Icons64x64_1::Icons64x64_1, Icons64x64_1::ShowDown2);
		$this->buttons[self::CURRENT]->setBgcolor('0048');
	}
	
	function hideClientList($login)
	{
		ClientList::Create($login)->hide();
		$this->visibilities[self::CURRENT] = false;
		$this->buttons[self::CURRENT]->setIconStyle(Icons64x64_1::Icons64x64_1, Icons64x64_1::ShowUp2);
		$this->buttons[self::CURRENT]->setBgcolor('0008');
	}
	
	function showAdminClientList($login)
	{
		if($this->recipientIsAdmin)
		{
			$clientList = ClientList::Create(Group::Get('admin'));
			$clientList->setPosition($this->posX + .1, $this->posY - $this->sizeY - .6);
			$clientList->setSize($this->sizeX * .8);
			$clientList->show($login);
			$this->visibilities[self::ADMIN] = true;
			$this->buttons[self::ADMIN]->setIconStyle(Icons64x64_1::Icons64x64_1, Icons64x64_1::ShowDown2);
			$this->buttons[self::ADMIN]->setBgcolor('0048');
		}
	}
	
	function hideAdminClientList($login)
	{
		if($this->recipientIsAdmin)
		{
			ClientList::Create(Group::Get('admin'))->hide($login);
			$this->visibilities[self::ADMIN] = false;
			$this->buttons[self::ADMIN]->setIconStyle(Icons64x64_1::Icons64x64_1, Icons64x64_1::ShowUp2);
			$this->buttons[self::ADMIN]->setBgcolor('0008');
		}
	}
	
	function showChannelTree($login)
	{
		$channelTree = ChannelTree::Create($login);
		$channelTree->setPosition($this->posX + $this->sizeX + 1, $this->posY - $this->sizeY * 2 / 3 - .2);
		$channelTree->setSize($this->sizeX * .8);
		$channelTree->show();
		$this->visibilities[self::CHANNELS] = true;
		$this->buttons[self::CHANNELS]->setIconStyle(Icons64x64_1::Icons64x64_1, Icons64x64_1::ShowRight2);
		$this->buttons[self::CHANNELS]->setBgcolor('0408');
	}
	
	function hideChannelTree($login)
	{
		ChannelTree::Create($login)->hide();
		$this->visibilities[self::CHANNELS] = false;
		$this->buttons[self::CHANNELS]->setIconStyle(Icons64x64_1::Icons64x64_1, Icons64x64_1::ShowLeft2);
		$this->buttons[self::CHANNELS]->setBgcolor('0008');
	}
	
	function toggleMain($login)
	{
		if($this->visibilities[self::MAIN])
		{
			$this->status->setVisibility(false);
			foreach($this->buttons as $button)
				$button->setVisibility(false);
			$this->hideClientList($login);
			$this->hideAdminClientList($login);
			$this->hideChannelTree($login);
			$this->visibilities[self::MAIN] = false;
			$this->infoButton->setVisibility(false);
		}
		else
		{
			$this->status->setVisibility(true);
			foreach($this->buttons as $button)
				$button->setVisibility(true);
			$this->visibilities[self::MAIN] = true;
			$this->infoButton->setVisibility(true);
		}
		$this->redraw();
	}
	
	function toggleClientList($login)
	{
		if($this->visibilities[self::CURRENT])
			$this->hideClientList($login);
		else
		{
			$this->hideAdminClientList($login);
			$this->showClientList($login);
		}
		$this->redraw();
	}
	
	function toggleAdminClientList($login)
	{
		if($this->recipientIsAdmin)
		{
			if($this->visibilities[self::ADMIN])
				$this->hideAdminClientList($login);
			else
			{
				$this->hideClientList($login);
				$this->showAdminClientList($login);
			}
		}
		$this->redraw();
	}
	
	function toggleChannelTree($login)
	{
		if($this->visibilities[self::CHANNELS])
			$this->hideChannelTree($login);
		else
			$this->showChannelTree($login);
		$this->redraw();
	}
	
	protected function onResize($oldX, $oldY)
	{
		$tierHeight = $this->sizeY / 3;
		$this->logo->setSize(2 * $tierHeight, 2 * $tierHeight);
		$this->status->setSize($this->sizeX - $tierHeight * ($this->recipientIsAdmin ? 3 : 2), 2 * $tierHeight);
		if($this->recipientIsAdmin)
		{
			$this->buttons[self::CURRENT]->setSize($this->sizeX * .4, $tierHeight);
			$this->buttons[self::ADMIN]->setSize($this->sizeX * .3, $tierHeight);
			$this->buttons[self::CHANNELS]->setSize($this->sizeX * .3, $tierHeight);
			$this->buttons[self::COMMENT_ON]->setSize($tierHeight - .1, $tierHeight - .1);
			$this->buttons[self::COMMENT_OFF]->setSize($tierHeight - .1, $tierHeight - .1);
			$this->commentFrame->setSize($tierHeight, 2 * $tierHeight);
			$this->infoButton->setPosition($this->sizeX - $tierHeight, -2 * $tierHeight);
		}
		else
		{
			$this->buttons[self::CURRENT]->setSize($this->sizeX * .6, $tierHeight);
			$this->buttons[self::CHANNELS]->setSize($this->sizeX * .4, $tierHeight);
			$this->infoButton->setPosition($this->sizeX, -2 * $tierHeight);
		}
		$this->mainFrame->setSize($this->sizeX + 1, $this->sizeY + 1);
	}
	
	function destroy()
	{
		parent::destroy();
		$this->mainFrame->destroy();
		$this->commentFrame->destroy();
	}
}

?>