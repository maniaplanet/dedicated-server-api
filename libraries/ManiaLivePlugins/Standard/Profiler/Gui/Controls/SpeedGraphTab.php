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
use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\Quad;
use ManiaLive\Gui\Controls\Frame;
use ManiaLive\Event\Dispatcher;
use ManiaLivePlugins\Standard\Profiler\Listener as MonitorListener;
use ManiaLivePlugins\Standard\Profiler\Event as MonitorEvent;

class SpeedGraphTab extends \ManiaLive\Gui\Controls\Tabbable implements MonitorListener
{
	const RESPONSE_OPTIMAL = 50;
	const RESPONSE_MINIMAL = 10;
	
	private $bars = array();
	private $cpuStats = array();
	
	private $barsFrame;
	private $optimalLineFrame;
	private $optimalLine;
	private $criticalLineFrame;
	private $criticalLine;
	
	function __construct()
	{
		$this->setTitle('Speed Graph');
		
		$this->barsFrame = new Frame(1, 1 - $this->sizeY, new Line());
		$this->addComponent($this->barsFrame);
		
		$this->criticalLineFrame = new Frame(0, 1 + (self::RESPONSE_MINIMAL * ($this->sizeY - 6) / self::RESPONSE_OPTIMAL) - $this->sizeY);
		$text = new Label(75, 3);
		$text->setTextColor('fff');
		$text->setText('Critical Response Time (1/'.self::RESPONSE_MINIMAL.' Second)');
		$text->setPosition(3, 3.5);
		$text->setTextSize(2);
		$this->criticalLineFrame->addComponent($text);
		
		$this->criticalLine = new Quad($this->sizeX - 2, 0.2);
		$this->criticalLine->setBgcolor('a00');
		$this->criticalLine->setPosX(1);
		$this->criticalLine->setValign('center');
		$this->criticalLineFrame->addComponent($this->criticalLine);
		$this->addComponent($this->criticalLineFrame);
		
		$this->optimalLineFrame = new Frame(0, -5);
		$text = new Label(75, 3);
		$text->setTextColor('fff');
		$text->setText('Optimal Response Time (1/'.self::RESPONSE_OPTIMAL.' Second)');
		$text->setPosition(3, 3.5);
		$text->setTextSize(2);
		$this->optimalLineFrame->addComponent($text);
		
		$this->optimalLine = new Quad($this->sizeX - 2, 0.2);
		$this->optimalLine->setBgcolor('0a0');
		$this->optimalLine->setPosX(1);
		$this->optimalLine->setValign('bottom');
		$this->optimalLineFrame->addComponent($this->optimalLine);
		$this->addComponent($this->optimalLineFrame);
		
		Dispatcher::register(MonitorEvent::getClass(), $this, MonitorEvent::ON_NEW_CPU_VALUE);
	}
	
	function onResize($oldX, $oldY)
	{
		$this->barsFrame->setPosY(1 - $this->sizeY);
		$width = ($this->sizeX - 2) / 10;
		$heightFactor = ($this->sizeY - 6) / max(self::RESPONSE_OPTIMAL, max($this->cpuStats));
		foreach($this->bars as $i => $bar)
			$bar->setSize($width, $this->cpuStats[$i] * $heightFactor);
		$this->criticalLineFrame->setPosY(1 + $heightFactor * self::RESPONSE_MINIMAL - $this->sizeY);
		$this->optimalLineFrame->setPosY(1 + $heightFactor * self::RESPONSE_OPTIMAL - $this->sizeY);
		$this->criticalLine->setSizeX($this->sizeX - 2);
		$this->optimalLine->setSizeX($this->sizeX - 2);
	}
	
	function onNewCpuValue($newValue)
	{
		$this->cpuStats[] = $newValue;
		if(count($this->cpuStats) > 10)
		{
			array_shift($this->cpuStats);
			$bar = array_shift($this->bars);
			$this->barsFrame->removeComponent($bar);
		}
		else
		{
			$bar = new Quad();
			$bar->setSizeX(($this->sizeX - 2) / 10);
			$bar->setValign('bottom');
		}
		
		$this->barsFrame->addComponent($bar);
		$this->bars[] = $bar;
		
		$iColor = 0;
		$heightFactor = ($this->sizeY - 6) / max(self::RESPONSE_OPTIMAL, max($this->cpuStats));
		foreach($this->bars as $i => $bar)
		{
			$color = dechex($iColor++);
			$bar->setBgcolor($color.$color.$color);
			$bar->setSizeY($this->cpuStats[$i] * $heightFactor);
		}
		
		$this->redraw();
	}
	
	function onNewMemoryValue($newValue) {}
	function onNewNetworkValue($newValue) {}
	
	function destroy()
	{
		Dispatcher::unregister(MonitorEvent::getClass(), $this);
		$this->barsFrame->destroy();
		$this->criticalLineFrame->destroy();
		$this->optimalLineFrame->destroy();
		$this->bars = array();
		$this->cpuStats = array();
		parent::destroy();
	}
}

?>