<?php

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
			array('Script', Icons128x32_1::RT_Script),
			array('Rounds', Icons128x32_1::RT_Rounds),
			array('Time Attack', Icons128x32_1::RT_TimeAttack),
			array('Team', Icons128x32_1::RT_Team),
			array('Laps', Icons128x32_1::RT_Laps),
			array('Stunts', Icons128x32_1::RT_Stunts),
			array('Cup', Icons128x32_1::RT_Cup)
		);
		
		// create layout for buttons ...
		$this->btn_container = new Frame(1, 15);
		$this->btn_container->applyLayout(new Line());
		$this->addComponent($this->btn_container);
		
		$this->setSize(count($this->modes) * 20 + 2, 40);
	}
	
	function onDraw()
	{
		// create button for each game mode ...
		$this->btn_container->clearComponents();
		foreach ($this->modes as $i => $mode)
		{
			$frame = new Frame(0, 2);
			$frame->setSize(20, 20);
			
			$ui = new Bgs1InRace();
			if ($i == Storage::getInstance()->gameInfos->gameMode)
			{
				$ui->setSubStyle(Bgs1InRace::NavButtonBlink);
			}
			else
			{
				$ui->setSubStyle(Bgs1InRace::BgTitleGlow);
			}
			$ui->setAction($this->callback('onClickMode', $i));
			$ui->setSize(20, 20);
			$frame->addComponent($ui);
			
			$ui = new Label(20);
			$ui->setText($mode[0]);
			$ui->setPosition(10, 1);
			$ui->setHalign('center');
			$frame->addComponent($ui);
			
			$ui = new Icons128x32_1(13);
			$ui->setAlign('center', 'center');
			$ui->setPosition(10, 13);
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
				Connection::getInstance()->restartMap();
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
			$dialog->setSize(125, 40);
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