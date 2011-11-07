<?php
/**
 * Admin Plugin - Allow admins to configure server on the fly
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\Standard\Admin\Gui\Windows;

use ManiaLib\Gui\Elements\Icons128x32_1;
use ManiaLib\Gui\Layouts\Line;
use ManiaLive\Data\Storage;
use ManiaLive\DedicatedApi\Connection;
use ManiaLive\DedicatedApi\Structures\GameInfos;
use ManiaLive\Gui\Window;
use ManiaLive\Gui\Controls\Frame;
use ManiaLive\Gui\Windows\Dialog;
use ManiaLive\Gui\Windows\Info;

use ManiaLivePlugins\Standard\Admin\Gui\Controls\ButtonMode;

class ChooseMode extends \ManiaLive\Gui\ManagedWindow
{
	static private $modes = array(
		GameInfos::GAMEMODE_SCRIPT => array('Script', Icons128x32_1::RT_Script),
		GameInfos::GAMEMODE_ROUNDS => array('Rounds', Icons128x32_1::RT_Rounds),
		GameInfos::GAMEMODE_TIMEATTACK => array('Time Attack', Icons128x32_1::RT_TimeAttack),
		GameInfos::GAMEMODE_TEAM => array('Team', Icons128x32_1::RT_Team),
		GameInfos::GAMEMODE_LAPS => array('Laps', Icons128x32_1::RT_Laps),
		GameInfos::GAMEMODE_CUP => array('Cup', Icons128x32_1::RT_Cup),
		GameInfos::GAMEMODE_STUNTS => array('Stunts', Icons128x32_1::RT_Stunts)
	);
	
	private $buttons;
	private $buttonsFrame;
	
	function onConstruct()
	{
		parent::onConstruct(count(self::$modes) * 20 + 2, 36);
		$this->setTitle('Choose Game Mode');
		
		// create layout for buttons ...
		$this->buttonsFrame = new Frame(1, -15);
		$this->buttonsFrame->setLayout(new Line());
		$this->addComponent($this->buttonsFrame);
		
		foreach(self::$modes as $mode => $nameAndSubStyle)
		{
			$button = new ButtonMode($nameAndSubStyle[0], $nameAndSubStyle[1]);
			$button->setAction($this->createAction(array($this, 'onClickMode'), $mode));
			
			$this->buttons[$mode] = $button;
			$this->buttonsFrame->addComponent($button);
		}
	}
	
	function onDraw()
	{
		$currentMode = Storage::getInstance()->gameInfos->gameMode;
		foreach($this->buttons as $mode => $button)
			if($mode == $currentMode)
				$button->setSelected();
			else
				$button->setNotSelected();
	}
	
	function dialogClosed($login, Window $dialog)
	{
		if($dialog->getAnswer() == Dialog::YES)
		{
			if(Storage::getInstance()->serverStatus->code == 4)
			{
				Connection::getInstance()->restartMap();
				$this->hide();
			}
			else
				$dialog->showAsDialog();
		}
	}
	
	function onClickMode($login, $mode)
	{
		try
		{
			Connection::getInstance()->setGameMode($mode);
			$dialog = Dialog::Create($login, false, 125, 40);
			$dialog->setTitle('Game Mode Changed!');
			$dialog->setText(
					'You have selected '.self::$modes[$mode][0].",\n".
					'New game mode will be set on map change!'."\n".
					'Do you want to restart now?');
			$dialog->setButtons(Dialog::YES | Dialog::NO);
			$dialog->addCloseCallback(array($this, 'dialogClosed'));
			$dialog->centerOnScreen();
			$dialog->showAsDialog();
			$this->show();
		}
		catch (\Exception $ex)
		{
			$win = Info::Create($login, false, 40, 23);
			$win->setTitle('Error');
			$win->setText($ex->getMessage());
			$win->centerOnScreen();
			$win->showAsDialog();
		}
	}
}

?>