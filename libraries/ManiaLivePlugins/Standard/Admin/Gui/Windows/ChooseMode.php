<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */
namespace ManiaLivePlugins\Standard\Admin\Gui\Windows;

use ManiaLib\Gui\Elements\Bgs1InRace;
use ManiaLib\Gui\Layouts\Line;
use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\Icons128x32_1;
use ManiaLive\Gui\Windowing\Controls\Frame;
use ManiaLive\Data\Storage;
use ManiaLive\DedicatedApi\Connection;
use ManiaLive\Gui\Windowing\Controls\ButtonResizeable;
use ManiaLive\Gui\Windowing\Controls\Panel;
use ManiaLive\Gui\Windowing\Windows\Dialog;
use ManiaLive\Gui\Windowing\Windows\Info;
use ManiaLive\Gui\Windowing\Window;

class ChooseMode extends \ManiaLive\Gui\Windowing\ManagedWindow
{
	protected $btn_container;
	protected $modes;
	protected $dialog;

	function initializeComponents()
	{
		$this->setTitle('Choose Game Mode');

		// define game modes ...
		$this->modes = array
		(
			array('Rounds', Icons128x32_1::RT_Rounds),
			array('Time Attack', Icons128x32_1::RT_TimeAttack),
			array('Team', Icons128x32_1::RT_Team),
			array('Laps', Icons128x32_1::RT_Laps),
			array('Stunts', Icons128x32_1::RT_Stunts),
			array('Cup', Icons128x32_1::RT_Cup)
		);

		// create layout for buttons ...
		$this->btn_container = new Frame(1, 6);
		$this->btn_container->applyLayout(new Line());
		$this->addComponent($this->btn_container);

		$this->setSize(count($this->modes) * 10 + 2, 17);
	}

	function onDraw()
	{
		// create button for each game mode ...
		$this->btn_container->clearComponents();
		foreach ($this->modes as $i => $mode)
		{
			$frame = new Frame();
			$frame->setSize(10, 6);

			$ui = new Bgs1InRace();
			if ($i == Storage::getInstance()->gameInfos->gameMode)
			{
				$ui->setSubStyle(Bgs1InRace::NavButtonBlink);
			}
			else
			{
				$ui->setSubStyle(Bgs1InRace::NavButton);
			}
			$ui->setAction($this->callback('onClickMode', $i));
			$ui->setSize(10, 10);
			$frame->addComponent($ui);

			$ui = new Label(8);
			$ui->setText($mode[0]);
			$ui->setPosition(5, 1);
			$ui->setHalign('center');
			$frame->addComponent($ui);

			$ui = new Icons128x32_1(6);
			$ui->setPosition(2, 3.5);
			$ui->setSubStyle($mode[1]);
			$frame->addComponent($ui);

			$this->btn_container->addComponent($frame);
		}
	}

	function dialogClosed($login, Window $dialog)
	{
		if ($dialog->getAnswer() == Dialog::YES)
		{
			if (Storage::getInstance()->serverStatus->code == 4)
			{
				Connection::getInstance()->restartChallenge();
				$this->hide();
			}
			else
			{
				$dialog->show();
			}
		}
	}

	function onClickMode($login, $mode)
	{
		try
		{
			Connection::getInstance()->setGameMode($mode);
			$dialog = Dialog::Create($login);
			$dialog->setSize(40, 20);
			$dialog->setTitle('Game Mode Changed!');
			$message = 'You have selected '.$this->modes[$mode][0].",\n";
			$message .= "New game mode will be set on map change!\n";
			$message .= "Do you want to restart now?";
			$dialog->setText($message);
			$dialog->setButtons(Dialog::YES | Dialog::NO);
			$this->show();
			$this->showDialog($dialog, 'dialogClosed');
		}
		catch (\Exception $ex)
		{
			$win = Info::Create($login);
			$win->setSize(40, 20);
			$win->setTitle('Error');
			$win->setText($ex->getMessage());
			$this->showDialog($win);
		}
	}
}

?>