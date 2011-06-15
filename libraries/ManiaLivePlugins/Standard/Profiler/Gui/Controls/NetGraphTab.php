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
namespace ManiaLivePlugins\Standard\Profiler\Gui\Controls;

use ManiaLib\Gui\Layouts\Line;
use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\Quad;
use ManiaLive\Gui\Windowing\Controls\Frame;

class NetGraphTab extends \ManiaLive\Gui\Windowing\Controls\Tab
{
	public $net_stats;

	protected $line_bars;
	protected $frm_lines;

	function initializeComponents()
	{
		$this->line_bars = new Frame();
		$this->line_bars->applyLayout(new Line());
		$this->addComponent($this->line_bars);

		$this->frm_lines = new Frame();
		$this->addComponent($this->frm_lines);
	}

	function beforeDraw()
	{
		if (empty($this->net_stats)) return;

		$max_height = $this->getSizeY() - 6;
		$max_width = $this->getSizeX() - 2;

		$sort = array();
		foreach ($this->net_stats as $stat)
			$sort[] = $stat[0] + $stat[1];
		$max = max($sort);
		$max *= 1.2;
		if ($max < 51200) $max = 51200;

		$part = $max / 4;

		$this->line_bars->clearComponents();
		foreach ($this->net_stats as $i => $stat)
		{
			$width = $max_width / 10;

			$frame = new Frame();
			$frame->setSize($width, $max_height);
			$frame->setPosition(1, $this->getSizeY() - 1);

			$bar_sent = new Quad($width, $max_height / $max * $stat[1]);
			$bar_sent->setStyle(null);
			$bar_sent->setBgcolor('a00');
			$bar_sent->setValign('bottom');
			$frame->addComponent($bar_sent);

			$bar_recv = new Quad($width, $max_height / $max * $stat[0]);
			$bar_recv->setStyle(null);
			$bar_recv->setBgcolor('0a0');
			$bar_recv->setPositionY(-$bar_sent->getSizeY());
			$bar_recv->setValign('bottom');
			$frame->addComponent($bar_recv);

			$this->line_bars->addComponent($frame);
		}

		$this->frm_lines->setPosition(1, $this->getSizeY() - 1);
		$this->frm_lines->clearComponents();
		for ($i = 1; $i <= 4; ++$i)
		{
			$pos_y = -$max_height / $max * ($part * $i);

			$ui = new Label(15);
			$ui->setPosition(3, $pos_y - 2.5);
			$ui->setText(round($part*$i/1024).' kb/s');
			$this->frm_lines->addComponent($ui);

			$ui = new Quad($max_width, 0.1);
			$ui->setPositionY($pos_y);
			$ui->setStyle(null);
			$ui->setBgcolor('fff');
			$this->frm_lines->addComponent($ui);
		}
	}

	function destroy()
	{
		unset($this->net_stats);
		parent::destroy();
	}
}

?>