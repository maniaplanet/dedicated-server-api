<?php

namespace ManiaLivePlugins\Standard\Profiler\Gui\Controls;

use ManiaLib\Gui\Layouts\Line;
use ManiaLib\Gui\Elements\BgsPlayerCard;
use ManiaLib\Gui\Elements\Icons64x64_1;
use ManiaLib\Gui\Elements\Bgs1;
use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\Quad;
use ManiaLive\Gui\Windowing\Controls\Frame;

class SpeedGraphTab extends \ManiaLive\Gui\Windowing\Controls\Tab
{
	protected $container;
	protected $container_lines;
	public $stats;
	
	const RESPONSE_OPTIMAL = 50;
	const RESPONSE_MINIMAL = 10;
	
	function initializeComponents()
	{
		$this->stats = array();
		
		$this->container = new Frame();
		$this->container->applyLayout(new Line());
		$this->addComponent($this->container);
		
		$this->container_lines = new Frame();
		$this->addComponent($this->container_lines);
	}
	
	function beforeDraw()
	{
		$this->container->setPosition(1, $this->getSizeY() - 1);
		$this->container->clearComponents();
		
		if (empty($this->stats))
			return;

		$max_height = $this->getSizeY() - 6;
		
		$max = max($this->stats);
		if ($max < self::RESPONSE_OPTIMAL) $max = self::RESPONSE_OPTIMAL;
		
		$width = ($this->getSizeX() - 2) / 10;
		
		$c = count($this->stats);
		for ($i = 0, $n = 0;$i < 10 && $i < $c; $i++)
		{
			$frame = new Frame();
			$frame->setSize($width, $max_height / $max * $this->stats[$i]);
			$frame->setValign('bottom');
			
			$color = dechex($i);

			$ui = new Quad();
			$ui->setStyle(BgsPlayerCard::BgsPlayerCard);
			$ui->setSubStyle(BgsPlayerCard::BgRacePlayerLine);
			$ui->setBgcolor($color.$color.$color);
			$ui->setSize($width, $max_height / $max * $this->stats[$i]);
			$frame->addComponent($ui);
			
			$this->container->addComponent($frame);
		}
		
		$bar_min_posy = $this->getSizeY() - 1 - ($max_height / $max * self::RESPONSE_MINIMAL);
		$bar_opt_posy = $this->getSizeY() - 1 - ($max_height / $max * self::RESPONSE_OPTIMAL);
		
		// create bar for minimum
		$this->container_lines->clearComponents();
		
		// add bar for minimum response time ...
		$txt = new Label(30, 3);
		$txt->setTextColor('fff');
		$txt->setText('Critical Response Time (1/' . self::RESPONSE_MINIMAL . ' Second)');
		$txt->setPosition(3, $bar_min_posy - 2.5);
		$txt->setTextSize(2);
		$this->container_lines->addComponent($txt);
		
		$bar = new Quad($this->getSizeX() - 2, 0.2);
		$bar->setStyle(null);
		$bar->setBgcolor('a00');
		$bar->setPosition(1, $bar_min_posy);
		$bar->setValign('bottom');
		$this->container_lines->addComponent($bar);
		
		// add bar for optimal response time ...
		$txt = new Label(30, 3);
		$txt->setTextColor('fff');
		$txt->setText('Optimal Response Time (1/' . self::RESPONSE_OPTIMAL . ' Second)');
		$txt->setPosition(3, $bar_opt_posy - 2.5);
		$txt->setTextSize(2);
		$this->container_lines->addComponent($txt);
		
		$bar = new Quad($this->getSizeX() - 2, 0.2);
		$bar->setStyle(null);
		$bar->setBgcolor('0a0');
		$bar->setPosition(1, $bar_opt_posy);
		$bar->setValign('bottom');
		$this->container_lines->addComponent($bar);
	}
	
	function destroy()
	{
		unset($this->stats);
		parent::destroy();
	}
}

?>