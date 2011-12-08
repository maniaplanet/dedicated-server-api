<?php
/**
 * Profiler Plugin - Show statistics about ManiaLive
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\Standard\Profiler\Gui\Controls;

use ManiaLib\Gui\Layouts\Line;
use ManiaLib\Gui\Elements\Quad;
use ManiaLib\Gui\Elements\Label;
use ManiaLive\Gui\Controls\Frame;
use ManiaLive\Event\Dispatcher;
use ManiaLivePlugins\Standard\Profiler\Listener as MonitorListener;
use ManiaLivePlugins\Standard\Profiler\Event as MonitorEvent;
use ManiaLivePlugins\Standard\Profiler\Profiler;

class MemoryTab extends \ManiaLive\Gui\Controls\Tabbable implements MonitorListener
{
	private $bars = array();
	private $lines = array();
	private $memoryStats = array();
	private $memoryLimit;
	
	private $barsFrame;
	private $linesFrame;
	
	function __construct()
	{
		$this->setTitle('Memory Graph');
		
		$this->memoryLimit = intval(str_replace('M', 1024*1024, ini_get('memory_limit')));
		if($this->memoryLimit == -1)
			$this->memoryLimit = Profiler::MEM_DEFAULT;
		
		$this->barsFrame = new Frame();
		$this->barsFrame->setLayout(new Line());
		$this->addComponent($this->barsFrame);
		
		$this->linesFrame = new Frame();
		$this->addComponent($this->linesFrame);
		
		$heightStep = ($this->sizeY - 6) / 4;
		for($i = 1; $i <= 4; ++$i)
		{
			$ui = new Frame();
			$ui->setPosition(0, $i * $heightStep);
			
			$line = new Quad();
			$line->setBgcolor('fff');
			$line->setSize($this->sizeX - 2, 0.2);
			$ui->addComponent($line);
			
			$text = new Label();
			$text->setText('$fff0% of total');
			$text->setPosition(3, 3.5);
			$ui->addComponent($text);
			
			$this->linesFrame->addComponent($ui);
			$this->lines[] = array($line, $text, $ui);
		}
		
		Dispatcher::register(MonitorEvent::getClass(), $this, MonitorEvent::ON_NEW_MEMORY_VALUE);
	}
	
	protected function onResize($oldX, $oldY)
	{
		$this->barsFrame->setPosition(1, 1 - $this->sizeY);
		$this->linesFrame->setPosition(1, 1 - $this->sizeY);
		
		$i = 1;
		$heightStep = ($this->sizeY - 6) / 4;
		foreach($this->lines as $line)
		{
			$line[0]->setSizeX($this->sizeX - 2);
			$line[2]->setPosY($i++ * $heightStep);
		}
		$width = ($this->sizeX - 2) / 10;
		$heightFactor = 2 * ($this->sizeY - 6) / ceil(3 * max($this->memoryStats));
		foreach($this->bars as $i => $bar)
			$bar->setSize($width, $heightFactor * $this->memoryStats[$i]);
	}
	
	function onNewMemoryValue($newValue)
	{
		$lastValue = end($this->memoryStats);
		$this->memoryStats[] = $newValue;
		if(count($this->memoryStats) > 10)
		{
			array_shift($this->memoryStats);
			$bar = array_shift($this->bars);
			$this->barsFrame->removeComponent($bar);
		}
		else
		{
			$bar = new Quad();
			$bar->setSizeX(($this->sizeX - 2) / 10);
			$bar->setValign('bottom');
		}
		
		if($lastValue === false || $lastValue == $newValue)
			$bar->setBgcolor('999');
		else if($lastValue < $newValue)
			$bar->setBgcolor('a00');
		else
			$bar->setBgcolor('0a0');
		
		$this->barsFrame->addComponent($bar);
		$this->bars[] = $bar;
		
		$heightFactor = 2 * ($this->sizeY - 6) / ceil(3 * max($this->memoryStats));
		foreach($this->bars as $i => $bar)
			$bar->setSizeY($this->memoryStats[$i] * $heightFactor);
		
		$i = 1;
		$step = round(37.5 * max($this->memoryStats) / $this->memoryLimit, 2);
		foreach($this->lines as $line)
			$line[1]->setText('$fff'.($i++*$step).'% of total');
		
		$this->redraw();
	}
	
	function onNewCpuValue($newValue) {}
	function onNewNetworkValue($newValue) {}
	
	function destroy()
	{
		Dispatcher::unregister(MonitorEvent::getClass(), $this);
		$this->barsFrame->destroy();
		$this->linesFrame->destroy();
		$this->bars = array();
		$this->lines = array();
		$this->memoryStats = array();
		parent::destroy();
	}
}

?>