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
namespace ManiaLivePlugins\Standard\Profiler\Gui\Windows;

use ManiaLive\Event\Dispatcher;
use ManiaLivePlugins\Standard\Profiler\Gui\Controls\NetGraphTab;
use ManiaLivePlugins\Standard\Profiler\Gui\Controls\OverviewTab;
use ManiaLivePlugins\Standard\Profiler\Gui\Controls\MemoryTab;
use ManiaLivePlugins\Standard\Profiler\Gui\Controls\SpeedGraphTab;
use ManiaLivePlugins\Standard\Profiler\Gui\Controls\PluginsTab;
use ManiaLive\Gui\Windowing\Controls\Tab;
use ManiaLive\Gui\Windowing\Controls\Tabview;
use ManiaLive\Gui\Windowing\Controls\Frame;
use ManiaLive\Gui\Windowing\Controls\Line;
use ManiaLive\Gui\Windowing\Controls\Panel;
use ManiaLib\Gui\Elements\Bgs1;
use ManiaLib\Gui\Elements\Quad;
use ManiaLib\Gui\Elements\Label;

class Stats extends \ManiaLive\Gui\Windowing\ManagedWindow
	implements \ManiaLive\Features\Tick\Listener
{
	protected $cpu_stats;
	protected $mem_stats;
	protected $tabview;
	protected $last_refresh;
	protected $net_stats;
	public $time_started;

	public static $open = 0;

	function initializeComponents()
	{
		$this->setTitle('Statistics');
		$this->setMaximizable();

		$this->cpu_stats = array();
		$this->mem_stats = array();

		// create tabview and fill it with content ...
		$this->tabview = new Tabview();

		// add overview tab
		$tab = new OverviewTab();
		$tab->setTitle('Overview');
		$tab->mem_stats =& $this->mem_stats;
		$tab->cpu_stats =& $this->cpu_stats;
		$this->tabview->addTab($tab);

		// add cpu tab
		$tab = new SpeedGraphTab();
		$tab->setTitle('Speed Graph');
		$tab->stats =& $this->cpu_stats;
		$this->tabview->addTab($tab);

		// add memory tab
		$tab = new MemoryTab();
		$tab->setTitle('Memory Graph');
		$tab->stats =& $this->mem_stats;
		$this->tabview->addTab($tab);

		// add network tab
		$tab = new NetGraphTab();
		$tab->setTitle('Network Graph');
		$tab->net_stats =& $this->net_stats;
		$this->tabview->addTab($tab);

		// add plugins tab
		$tab = new PluginsTab();
		$tab->setTitle('Plugins');
		$this->tabview->addTab($tab);

		$this->addComponent($this->tabview);
	}

	function onDraw()
	{
		$this->tabview->getTab(0)->time_started = $this->time_started;

		$this->tabview->setPosition(1, 6);
		$this->tabview->setSize($this->getSizeX() - 2, $this->getSizeY() - 7);
	}

	function onShow()
	{
		self::$open++;

		// subscribe to ticker ..
		Dispatcher::register(\ManiaLive\Features\Tick\Event::getClass(), $this);

		// refresh the plugins list ...
		$this->tabview->getTab(4)->refreshList();
	}

	function onHide()
	{
		self::$open--;

		// subscribe to ticker ..
		Dispatcher::unregister(\ManiaLive\Features\Tick\Event::getClass(), $this);
	}

	function onTick()
	{
		$diff = time() - $this->last_refresh;

		switch ($this->tabview->getActiveTabId())
		{
			case 0:
				if ($diff < 1) return;
				$this->last_refresh = time();
				$this->show();
				break;

			case 1:
				if ($diff < 1) return;
				$this->last_refresh = time();
				$this->show();
				break;

			case 2:
				if ($diff < 3) return;
				$this->last_refresh = time();
				$this->show();
				break;

			case 3:
				if ($diff < 1) return;
				$this->last_refresh = time();
				$this->show();
				break;
		}
	}

	function addCpuUsage($value)
	{
		$this->cpu_stats[] = $value;
		if (count($this->cpu_stats) > 10) array_shift($this->cpu_stats);
	}

	function addMemoryUsage($value)
	{
		$this->mem_stats[] = $value;
		if (count($this->mem_stats) > 10) array_shift($this->mem_stats);
	}

	function addNetUsage($receive, $send)
	{
		$this->net_stats[] = array($receive, $send);
		if (count($this->net_stats) > 10) array_shift($this->net_stats);
	}

	function destroy()
	{
		Dispatcher::unregister(\ManiaLive\Features\Tick\Event::getClass(), $this);
		unset($this->mem_stats);
		unset($this->cpu_stats);
		unset($this->net_stats);
		unset($this->time_started);
		parent::destroy();
	}
}

?>