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

class NetGraphTab extends \ManiaLive\Gui\Controls\Tabbable implements MonitorListener
{
	private $bars = array();
	private $lines = array();
	private $networkStats = array();
	private $networkSums = array();
	
	private $barsFrame;
	private $linesFrame;
	
	function __construct()
	{
		$this->setTitle('Network Graph');
		
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
			$text->setText('$fff0 kb/s');
			$text->setPosition(3, 3.5);
			$ui->addComponent($text);
			
			$this->linesFrame->addComponent($ui);
			$this->lines[] = array($line, $text, $ui);
		}
		
		Dispatcher::register(MonitorEvent::getClass(), $this, MonitorEvent::ON_NEW_NETWORK_VALUE);
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
		$heightFactor = ($this->sizeY - 6) / max(51200, 1.2 * max($this->networkSums));
		foreach($this->bars as $i => $bars)
		{
			$bars[0]->setSize($width, $this->networkStats[$i][1] * $heightFactor);
			$bars[1]->setSize($width, $this->networkStats[$i][0] * $heightFactor);
			$bars[2]->setSize($width);
			$bars[1]->setPosY($bars[0]->getRealSizeY());
		}
	}
	
	function onNewNetworkValue($newValue)
	{
		$this->networkSums[] = $newValue[0] + $newValue[1];
		$this->networkStats[] = $newValue;
		if(count($this->networkSums) > 10)
		{
			array_shift($this->networkSums);
			array_shift($this->networkStats);
			$bars = array_shift($this->bars);
			$this->barsFrame->removeComponent($bars[2]);
		}
		else
		{
			$frame = new Frame();
			$frame->setSizeX(($this->sizeX - 2) / 10);
			$sentBar = new Quad();
			$sentBar->setSizeX(($this->sizeX - 2) / 10);
			$sentBar->setBgcolor('a00');
			$sentBar->setValign('bottom');
			$frame->addComponent($sentBar);
			$receivedBar = new Quad();
			$receivedBar->setSizeX(($this->sizeX - 2) / 10);
			$receivedBar->setBgcolor('0a0');
			$receivedBar->setValign('bottom');
			$frame->addComponent($receivedBar);
			
			$bars = array($sentBar, $receivedBar, $frame);
		}
		
		$this->barsFrame->addComponent($bars[2]);
		$this->bars[] = $bars;
		
		$heightFactor = ($this->sizeY - 6) / max(51200, 1.2 * max($this->networkSums));
		foreach($this->bars as $i => $bars)
		{
			$bars[0]->setSizeY($this->networkStats[$i][1] * $heightFactor);
			$bars[1]->setSizeY($this->networkStats[$i][0] * $heightFactor);
			$bars[1]->setPosY($bars[0]->getRealSizeY());
		}
		
		$i = 1;
		$step = max(51200, 1.2 * max($this->networkSums)) / 4096;
		foreach($this->lines as $line)
			$line[1]->setText('$fff'.round($i++*$step).' kb/s');
		
		$this->redraw();
	}
	
	function onNewCpuValue($newValue) {}
	function onNewMemoryValue($newValue) {}
	
	function destroy()
	{
		Dispatcher::unregister(MonitorEvent::getClass(), $this);
		$this->barsFrame->destroy();
		$this->linesFrame->destroy();
		$this->bars = array();
		$this->lines = array();
		$this->networkStats = array();
		$this->networkSums = array();
		parent::destroy();
	}
}

?>