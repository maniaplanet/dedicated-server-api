<?php

namespace ManiaLivePlugins\Standard\Profiler\Gui\Controls;

use ManiaLib\Gui\Elements\BgRaceScore2;
use ManiaLib\Gui\Elements\BgsPlayerCard;
use ManiaLib\Gui\Elements\Quad;
use ManiaLib\Gui\Layouts\Line;
use ManiaLib\Gui\Elements\Label;
use ManiaLivePlugins\Standard\Profiler\Profiler;
use ManiaLive\Gui\Windowing\Controls\Frame;

class MemoryTab extends \ManiaLive\Gui\Windowing\Controls\Tab
{
	public $stats;
	protected $cnt_bars;
	protected $cnt_lines;
	protected $memory_limit;
	protected $diffs;
	protected $stat_last;
	protected $stat_removed;
	
	function initializeComponents()
	{
		$this->diffs = array();
		$this->memory_limit = intval(str_replace('M', 1024*1024, ini_get('memory_limit')));
		if ($this->memory_limit == -1) $this->memory_limit = Profiler::MEM_DEFAULT;
		
		$this->cnt_bars = new Frame();
		$this->cnt_bars->applyLayout(new Line());
		$this->addComponent($this->cnt_bars);
		
		$this->cnt_lines = new Frame();
		$this->addComponent($this->cnt_lines);
	}
	
	function beforeDraw()
	{
		$this->cnt_bars->clearComponents();
		$this->cnt_bars->setPosition(1, $this->getSizeY() - 1);
		
		$this->cnt_lines->clearComponents();
		$this->cnt_lines->setPosition(1, $this->getSizeY() - 1);
		
		if (empty($this->stats)) return;
		
		$max_height = $this->getSizeY() - 6;
		$max_mem = max($this->stats);
		$max_width = $this->getSizeX() - 2;
		
		$percent = ceil(100 / $this->memory_limit * $max_mem);
		$max_percent = ceil($percent * 1.5);
		$step = $max_percent / 4;

		// draw bars
		foreach ($this->stats as $i => $stat)
		{
			$frame = new Frame();
			$frame->setSizeX($max_width / 10);
			
			// show in comparison to maximal allowed
			$ui = new Quad();
			$ui->setSizeX($frame->getSizeX());
			$ui->setSizeY(($max_height / $max_percent) * (100 / $this->memory_limit * $stat));
			$ui->setStyle(null);
			
			// calculate delta to last usage
			if ($i == 0)
				$diff = $stat - $this->stat_removed;
			else
				$diff = $stat - $this->stat_last;
			
			// display different colors for freeing or reserving of memory
			if ($diff == 0 || abs($diff) == $stat)
			{
				$ui->setBgcolor('999');
			}
			elseif ($diff > 0)
			{
				$ui->setBgcolor('a00');
			}

			else 
			{
				$ui->setBgcolor('0a0');
			}
			
			$ui->setValign('bottom');
			$frame->addComponent($ui);
			
			$this->stat_last = $stat;
			if ($i == 0) $this->stat_removed = $stat;
			
			$this->cnt_bars->addComponent($frame);
		}
		
		// draw lines
		for ($i = 1; $i <= 4; $i++)
		{
			$ui = new Quad();
			$ui->setStyle(null);
			$ui->setBgcolor('fff');
			$ui->setSize($this->getSizeX() - 2, 0.1);
			$ui->setPosition(0, -$max_height / 4 * $i);
			$this->cnt_lines->addComponent($ui);
			
			$ui = new Label();
			$ui->setText(($step * $i) . '% of total');
			$ui->setPosition(3, -$max_height / 4 * $i - 2.5);
			$this->cnt_lines->addComponent($ui);
		}
	}
	
	function destroy()
	{
		unset($this->stats);
		parent::destroy();
	}
}

?>