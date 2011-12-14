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
use ManiaLib\Gui\Layouts\Column;
use ManiaLib\Gui\Layouts\Line;
use ManiaLive\Gui\Controls\Frame;
use ManiaLivePlugins\Standard\TeamSpeak\Connection as TSConnection;
use ManiaLivePlugins\Standard\TeamSpeak\Gui\Images as TSImages;
use ManiaLivePlugins\Standard\TeamSpeak\Structures\Client as ClientStruct;
use ManiaLivePlugins\Standard\TeamSpeak\Structures\Channel as ChannelStruct;

/**
 * Description of Client
 */
class Client extends \ManiaLive\Gui\Control implements \ManiaLivePlugins\Standard\TeamSpeak\Structures\Observer
{
	private $background;
	private $nickname;
	private $comment;
	private $frame;
	
	private $client;
	private $actionCommentOn;
	private $actionCommentOff;
	
	function __construct(ClientStruct $client)
	{
		$this->client = $client;
		$this->client->addObserver($this);
		$tsConnection = TSConnection::getInstance();
		$this->actionCommentOn = $this->createAction(array($tsConnection, 'toggleClientComment'), $this->client->clientId, true);
		$this->actionCommentOff = $this->createAction(array($tsConnection, 'toggleClientComment'), $this->client->clientId, false);
		
		$this->background = new Quad();
		$this->background->setBgcolor('0008');
		$this->addComponent($this->background);
		
		$layout = new Line();
		$layout->setBorderWidth(.5);
		$layout->setMarginWidth(.5);
		$this->frame = new Frame(0, 0, $layout);
		$this->addComponent($this->frame);
		
		$this->nickname = new Label();
		$this->nickname->setTextSize(1);
		$this->nickname->setValign('center2');
		$this->frame->addComponent($this->nickname);
		
		$this->comment = new Quad();
		$this->comment->setValign('center');
		$this->frame->addComponent($this->comment);
		
		$this->onUpdate();
		$this->setSizeY(5);
	}
	
	function enableCommentButton($enable)
	{
		$this->comment->setAction($enable ? ($this->client->isCommentator ? $this->actionCommentOff : $this->actionCommentOn) : null);
		$this->comment->setVisibility($enable || $this->client->isCommentator);
	}
	
	function onUpdate()
	{
		$this->nickname->setText($this->client->nicknameToShow);
		
		$images = TSImages::getInstance();
		$channel = ChannelStruct::Get($this->client->channelId);
		if($this->client->isCommentator)
		{
			$this->comment->setImage($images->clientCommentOn, true);
			$this->comment->setImageFocus($channel && $channel->commentatorEnabled ? $images->clientCommentOff : $images->clientCommentNeutral, true);
		}
		else
		{
			$this->comment->setImage($channel && $channel->commentatorEnabled ? $images->clientCommentOff : $images->clientCommentNeutral, true);
			$this->comment->setImageFocus($images->clientCommentOn, true);
		}
		$this->redraw();
	}
	
	protected function onResize($oldX, $oldY)
	{
		$this->background->setSize($this->sizeX, $this->sizeY);
		$this->nickname->setSize($this->sizeX - $this->sizeY - 1);
		$this->nickname->setPosY(-$this->sizeY / 2);
		$this->comment->setSize($this->sizeY - 1, $this->sizeY - 1);
		$this->comment->setPosY(-$this->sizeY / 2);
	}
	
	protected function onDraw()
	{
		if($this->client->isAway)
		{
			$this->background->setBgcolor('1128');
			$this->nickname->setTextColor('fff8');
		}
		else
		{
			$this->background->setBgcolor('0048');
			$this->nickname->setTextColor('ffff');
		}
	}
	
	function destroy()
	{
		parent::destroy();
		$this->client->removeObserver($this);
	}
}

?>