<?php
/**
 * AutoQueue plugin - Manage a queue of spectators
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\Standard\AutoQueue\Gui\Windows;

use ManiaLib\Gui\Elements\Bgs1InRace;
use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Layouts\VerticalFlow;
use ManiaLive\Gui\Controls\Frame;
use ManiaLive\Gui\Controls\ButtonResizable;

/**
 * Description of Queue
 */
class Queue extends \ManiaLive\Gui\Window
{
	static private $background;
	static private $title;
	static private $queuedPlayers;
	static private $labelsByLogin = array();
	
	static private $enterQueueAction;
	static private $leaveQueueAction;
	
	private $queueButton;
	
	static function Initialize($enterQueueAction, $leaveQueueAction)
	{
		self::$background = new Bgs1InRace(22, 35);
		self::$background->setHalign('center');
		
		self::$title = new Label(20, 5);
		self::$title->setHalign('center');
		self::$title->setPosition(0, -.5);
		self::$title->setText('Queue');
		
		$layout = new VerticalFlow(22, 24);
		$layout->setMarginWidth(1);
		$layout->setBorderWidth(1);
		self::$queuedPlayers = new Frame(0, -5, $layout);
		
		self::$enterQueueAction = $enterQueueAction;
		self::$leaveQueueAction = $leaveQueueAction;
	}
	
	static function Add($player)
	{
		if(!isset(self::$labelsByLogin[$player->login]))
		{
			$ui = new Label(20, 3);
			$ui->setStyle(Label::TextStaticVerySmall);
			$ui->setText($player->nickname);
			self::$labelsByLogin[$player->login] = $ui;
			self::$queuedPlayers->addComponent($ui);
			self::$queuedPlayers->redraw();
			
			if((count(self::$labelsByLogin) - 1) % 8 == 0)
			{
				self::$background->setSizeX(self::$background->getSizeX() + 21);
				self::$queuedPlayers->setPosX(self::$queuedPlayers->getPosX() - 10.5);
			}
		}
	}
	
	static function Remove($queuePos)
	{
		if(isset(self::$labelsByLogin[$player->login]))
		{
			self::$queuedPlayers->removeComponent(self::$labelsByLogin[$player->login]);
			self::$queuedPlayers->redraw();
			unset(self::$labelsByLogin[$player->login]);
			
			if(count(self::$labelsByLogin) % 8 == 0)
			{
				self::$background->setSizeX(self::$background->getSizeX() - 21);
				self::$queuedPlayers->setPosX(self::$queuedPlayers->getPosX() + 10.5);
			}
		}
	}
	
	function onConstruct()
	{
		$this->queueButton = new ButtonResizable(20, 4);
		$this->queueButton->setHalign('center');
		$this->queueButton->setPosY(-30.5);
		
		$this->addComponent(self::$background);
		$this->addComponent(self::$title);
		$this->addComponent(self::$queuedPlayers);
		$this->addComponent($this->queueButton);
	}
	
	function setIsQueued()
	{
		$this->queueButton->setText('Leave queue');
		$this->queueButton->setAction(self::$leaveQueueAction);
	}
	
	function setIsUnqueued()
	{
		$this->queueButton->setText('Enter queue');
		$this->queueButton->setAction(self::$enterQueueAction);
	}
}

?>
