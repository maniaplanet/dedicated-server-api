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

namespace ManiaLivePlugins\Standard\TeamSpeak\Windows;

use ManiaLib\Gui\Elements\UIConstructionSimple_Buttons;
use ManiaLivePlugins\Standard\TeamSpeak\Controls\MainButton;
use ManiaLivePlugins\Standard\TeamSpeak\Images as TSImages;
use ManiaLivePlugins\Standard\TeamSpeak\Structures\Channel;

/**
 * Description of Main
 */
class Main extends \ManiaLive\Gui\Window
{
	/** @var MainButton */
	private $logo;
	/** @var MainButton */
	private $freeTalk;
	/** @var MainButton */
	private $comments;
	/** @var MainButton */
	private $status;
	private $helpButton;
	
	protected function onConstruct()
	{
		$this->logo = new MainButton();
		$this->logo->setBgcolorFocus('8888');
		$this->logo->showIcon();
		$this->addComponent($this->logo);
		
		$this->freeTalk = new MainButton();
		$this->freeTalk->setBgcolorFocus('8888');
		$this->freeTalk->setText('Free talk');
		$this->freeTalk->setVisibility(false);
		$this->freeTalk->showText();
		$this->freeTalk->setAction(Channel::$moveActions[Channel::FREE_TALK]);
		$this->addComponent($this->freeTalk);
		
		$this->comments = new MainButton();
		$this->comments->setBgcolorFocus('8888');
		$this->comments->setText('Comments');
		$this->comments->setVisibility(false);
		$this->comments->showText();
		$this->comments->setAction(Channel::$moveActions[Channel::COMMENTS]);
		$this->addComponent($this->comments);
		
		$this->status = new MainButton();
		$this->status->setBgcolorFocus('8888');
		$this->status->setVisibility(false);
		$this->status->showText();
		$this->status->enableAutonewline();
		$this->addComponent($this->status);
		
		$this->helpButton = new UIConstructionSimple_Buttons();
		$this->helpButton->setSubStyle(UIConstructionSimple_Buttons::Help);
		$this->helpButton->setAlign('center', 'center');
		$this->helpButton->setAction($this->createAction(array($this, 'toggleStatus')));
		$this->addComponent($this->helpButton);
		
		$this->setSize(75, 10);
		$this->setPosition(-160, 45);
	}
	
	function setNotConnected()
	{
		$this->logo->setIconImage(TSImages::getInstance()->tsGrey);
		$this->logo->setUrl(\ManiaLivePlugins\Standard\TeamSpeak\Config::getInstance()->getConnectUrl($this->getRecipient()));
		$this->status->setText('Connect to the local TeamSpeak server. ManiaPlanet will be minimized.');
	}
	
	function setConnected($channelId, $teamId)
	{
		if($teamId == -1)
		{
			$this->freeTalk->setVisibility(true);
			$this->freeTalk->setBgcolor('0008');
			$this->freeTalk->enableLinks();
			$this->comments->setVisibility(true);
			$this->comments->setBgcolor('0008');
			$this->comments->enableLinks();
			if($channelId == Channel::$serverIds[Channel::FREE_TALK])
			{
				$this->freeTalk->disableLinks();
				$this->freeTalk->setBgcolor('8888');
				$status = 'You\'re currently connected on the free talk channel.';
			}
			else if($channelId == Channel::$serverIds[Channel::COMMENTS])
			{
				$this->comments->disableLinks();
				$this->comments->setBgcolor('8888');
				$status = 'You\'re currently listening to commentators.';
			}
			else
			{
				$status = 'Click to join the free talk channel or to listen to commentators.';
			}
		}
		else
		{
			$this->freeTalk->setVisibility(false);
			$this->comments->setVisibility(false);
			if($channelId == Channel::$serverIds[$teamId])
			{
				$this->logo->setAction(null);
				$status = 'You\'re currently connected on your team\'s channel.';
			}
			else
			{
				$this->logo->setAction(Channel::$moveActions[$teamId]);
				$status = 'Click to join your team\'s channel';
			}
		}
		$this->logo->setIconImage(TSImages::getInstance()->tsGreen);
		$this->status->setText($status);
	}
	
	function setError()
	{
		$this->logo->setIconImage(TSImages::getInstance()->tsRed);
		$this->logo->setAction(null);
		$this->freeTalk->setVisibility(false);
		$this->comments->setVisibility(false);
		$this->status->setText('Connection problem between ManiaLive and TeamSpeak.');
	}
	
	function toggleStatus($login)
	{
		$this->status->setVisibility(!$this->status->isVisible());
		$this->redraw();
	}
	
	protected function onDraw()
	{
		$this->logo->setSize($this->sizeY, $this->sizeY);
		$this->helpButton->setSize($this->sizeY / 1.5, $this->sizeY / 1.5);
		$this->freeTalk->setSize(2 * $this->sizeY, $this->sizeY / 2);
		$this->freeTalk->setPosition($this->sizeY);
		$this->comments->setSize(2 * $this->sizeY, $this->sizeY / 2);
		$this->comments->setPosition($this->sizeY, -$this->sizeY / 2);
		$this->status->setSize(4.5 * $this->sizeY, $this->sizeY);
		if($this->freeTalk->isVisible())
		{
			$this->status->setPosition(3 * $this->sizeY);
			$this->helpButton->setPosition(3 * $this->sizeY);
		}
		else
		{
			$this->status->setPosition($this->sizeY);
			$this->helpButton->setPosition($this->sizeY);
		}
	}
	
	function destroy()
	{
		parent::destroy();
		$this->logo->destroy();
		$this->status->destroy();
	}
}

?>