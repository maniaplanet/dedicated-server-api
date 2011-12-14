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

namespace ManiaLivePlugins\Standard\TeamSpeak\Gui\Controls;

use ManiaLib\Gui\Elements\Quad;
use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Layouts\Line;
use ManiaLive\Gui\Controls\Frame;
use ManiaLivePlugins\Standard\TeamSpeak\Config;
use ManiaLivePlugins\Standard\TeamSpeak\Connection as TSConnection;
use ManiaLivePlugins\Standard\TeamSpeak\Gui\Images as TSImages;
use ManiaLivePlugins\Standard\TeamSpeak\Gui\Controls\HackedQuad;
use ManiaLivePlugins\Standard\TeamSpeak\Gui\Controls\MainButton;
use ManiaLivePlugins\Standard\TeamSpeak\Structures\Channel as ChannelStruct;

/**
 * Description of Channel
 */
class Channel extends \ManiaLive\Gui\Control implements \ManiaLivePlugins\Standard\TeamSpeak\Structures\Observer
{
	private $background;
	private $nbClients;
	private $name;
	private $comment;
	private $frame;
	
	private $channel;
	private $actionMove;
	private $actionCommentOn;
	private $actionCommentOff;
	
	function __construct(ChannelStruct $channel)
	{
		$this->channel = $channel;
		$this->channel->addObserver($this);
		$tsConnection = TSConnection::getInstance();
		$this->actionMove = $this->createAction(array($tsConnection, 'movePlayer'), $this->channel->channelId);
		$this->actionCommentOn = $this->createAction(array($tsConnection, 'toggleChannelComment'), $this->channel->channelId, true);
		$this->actionCommentOff = $this->createAction(array($tsConnection, 'toggleChannelComment'), $this->channel->channelId, false);
		
		$this->background = new HackedQuad();
		$this->background->setBgcolorFocus('f808');
		$this->addComponent($this->background);
		
		$layout = new Line();
		$layout->setBorderWidth(.5);
		$layout->setMarginWidth(.5);
		$this->frame = new Frame(0, 0, $layout);
		$this->addComponent($this->frame);
		
		$this->nbClients = new MainButton();
		$this->nbClients->setValign('center');
		$this->nbClients->showText();
		$this->frame->addComponent($this->nbClients);
		
		$this->name = new Label();
		$this->name->setStyle(Label::TextRaceStaticSmall);
		$this->name->setValign('center2');
		$this->frame->addComponent($this->name);
		
		$this->comment = new Quad();
		$this->comment->setValign('center');
		$this->frame->addComponent($this->comment);
		
		$this->onUpdate();
		$this->setSizeY(5);
	}
	
	function getChannel()
	{
		return $this->channel;
	}
	
	function setBgcolor($bgcolor)
	{
		$this->background->setBgcolor($bgcolor);
	}
	
	function enableCommentButton($enable)
	{
		$this->comment->setAction($enable ? ($this->channel->commentatorEnabled ? $this->actionCommentOff : $this->actionCommentOn) : null);
		$this->comment->setVisibility($enable || $this->channel->commentatorEnabled);
	}
	
	function useNothing()
	{
		$this->background->setAction(null);
		$this->background->setUrl(null);
	}
	
	function useAction()
	{
		$this->background->setAction($this->actionMove);
		$this->background->setUrl(null);
	}
	
	function useUrl($login)
	{
		$this->background->setAction(null);
		$this->background->setUrl(Config::getInstance()->getConnectUrl($this->channel->serverPath, $login));
	}
	
	function onUpdate()
	{
		$this->nbClients->setText(count($this->channel->clients));
		$this->name->setText($this->channel->name);
		
		$images = TSImages::getInstance();
		if($this->channel->commentatorEnabled)
		{
			$this->comment->setImage($images->channelCommentOn, true);
			$this->comment->setImageFocus($images->channelCommentOff, true);
		}
		else
		{
			$this->comment->setImage($images->channelCommentOff, true);
			$this->comment->setImageFocus($images->channelCommentOn, true);
		}
		$this->redraw();
	}
	
	protected function onResize($oldX, $oldY)
	{
		$this->background->setSize($this->sizeX, $this->sizeY);
		$this->nbClients->setSize($this->sizeY - 1, $this->sizeY - 1);
		$this->name->setSize($this->sizeX - 2 * $this->sizeY, $this->sizeY - 1);
		$this->comment->setSize($this->sizeY - 1, $this->sizeY - 1);
		$this->frame->setPosY(-$this->sizeY / 2);
	}
	
	function destroy()
	{
		parent::destroy();
		$this->channel->removeObserver($this);
	}
}

?>