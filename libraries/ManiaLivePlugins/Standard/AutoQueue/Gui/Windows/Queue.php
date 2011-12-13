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
use ManiaLivePlugins\Standard\AutoQueue\Config;

/**
 * Description of Queue
 */
class Queue extends \ManiaLive\Gui\Window
{
	static private $background;
	static private $title;
	static private $queuedPlayers;
	static private $labelsByLogin = array();
	static private $columnSeparators = array();
	
	static private $enterQueueAction;
	static private $leaveQueueAction;
	
	private $queueButton;
	
	static function Initialize($enterQueueAction, $leaveQueueAction)
	{
		self::$background = new Bgs1InRace(32, 35);
		self::$background->setSubStyle(Bgs1InRace::BgCardList);
		self::$background->setHalign('center');
		
		self::$title = new Label(20, 5);
		self::$title->setStyle(Label::TrackerTextBig);
		self::$title->setHalign('center');
		self::$title->setPosition(0, -1);
		self::$title->setText('Queue');
		
		$layout = new VerticalFlow(32, 24);
		$layout->setMarginWidth(.2);
		$layout->setBorderWidth(.5);
		self::$queuedPlayers = new Frame(-15.5, -5.5, $layout);
		
		self::$enterQueueAction = $enterQueueAction;
		self::$leaveQueueAction = $leaveQueueAction;
	}
	
	static function Add($player)
	{
		if(!isset(self::$labelsByLogin[$player->login]))
		{
			if(count(self::$labelsByLogin) > 0 && count(self::$labelsByLogin) % 8 == 0)
			{
				self::$background->setSizeX(self::$background->getSizeX() + 31);
				self::$queuedPlayers->setPosX(self::$queuedPlayers->getPosX() - 15.5);
				$separator = new Bgs1InRace(.6, 28);
				$separator->setSubStyle(Bgs1InRace::Glow);
				$separator->setPosY(2);
				self::$columnSeparators[] = $separator;
				self::$queuedPlayers->addComponent($separator);
			}
			
			$ui = new Label(30, 3);
			$ui->setStyle(Label::TextPlayerCardName);
			$ui->setText($player->nickName);
			self::$labelsByLogin[$player->login] = $ui;
			self::$queuedPlayers->addComponent($ui);
			self::$queuedPlayers->redraw();
		}
	}
	
	static function Remove($player)
	{
		if(isset(self::$labelsByLogin[$player->login]))
		{
			self::$queuedPlayers->removeComponent(self::$labelsByLogin[$player->login]);
			self::$queuedPlayers->redraw();
			unset(self::$labelsByLogin[$player->login]);
			
			if(count(self::$labelsByLogin) > 0 && count(self::$labelsByLogin) % 8 == 0)
			{
				self::$background->setSizeX(self::$background->getSizeX() - 31);
				self::$queuedPlayers->setPosX(self::$queuedPlayers->getPosX() + 15.5);
				self::$queuedPlayers->removeComponent(array_pop(self::$columnSeparators));
			}
		}
	}
	
	static function Clear()
	{
		self::$background = null;
		self::$title = null;
		self::$queuedPlayers = null;
		self::$labelsByLogin = array();
		self::$columnSeparators = array();
		self::$enterQueueAction = null;
		self::$leaveQueueAction = null;
	}
	
	protected function onConstruct()
	{
		$this->queueButton = new ButtonResizable(20, 4);
		$this->queueButton->setHalign('center');
		$this->queueButton->setPosY(-30.5);
		
		$this->addComponent(self::$background);
		$this->addComponent(self::$title);
		$this->addComponent(self::$queuedPlayers);
		$this->addComponent($this->queueButton);
		
		$this->setPosition(Config::getInstance()->posX, Config::getInstance()->posY);
		$this->setIsUnqueued();
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
