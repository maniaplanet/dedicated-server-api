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
namespace ManiaLivePlugins\Standard\Dedimania\Gui\Windows;

use ManiaLive\Gui\Windowing\Controls\Frame;
use ManiaLive\Utilities\Time;
use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Layouts\Column;

class CheckpointTime extends \ManiaLive\Gui\Windowing\Window
{
	private $times_counter;
	private $times;
	private $container;

	function initializeComponents()
	{
		$this->times = array();
		$this->times_counter = 0;

		$this->container = new Frame();
		$layout = new Column();
		$layout->setDirection(Column::DIRECTION_UP);
		$this->container->applyLayout($layout);
		$this->addComponent($this->container);
	}

	function onDraw()
	{
		$this->container->clearComponents();

		$times = array_reverse($this->times);
		$bold = '';
		foreach ($times as $i => $time)
		{
			if ($i == 0)
			{
				$bold = '$o';
				$factor = 'f';
			}
			else
			{
				$bold = '';
				$factor = 'a';
			}

			if ($time <= 0)
				$color = '$0'.$factor.'0';
			else
				$color = '$'.$factor.'00';

			$ui = new Label(20, 4);
			$ui->setHalign('center');
			$ui->setText('$'.$factor.$factor.$factor.'CP #'.($this->times_counter - $i).' '.$bold.$color.Time::fromTM($time, true));
			$this->container->addComponent($ui);
		}
	}

	function addTime($time)
	{
		$this->times_counter++;
		$this->times[] = $time;
		if (count($this->times) * 4 > $this->getSizeY())
		{
			array_shift($this->times);
		}
	}

	function clearTimes()
	{
		$this->times_counter = 0;
		$this->times = array();
	}
}

?>