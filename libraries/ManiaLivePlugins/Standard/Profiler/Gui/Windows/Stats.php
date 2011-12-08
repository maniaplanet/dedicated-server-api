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

namespace ManiaLivePlugins\Standard\Profiler\Gui\Windows;

use ManiaLive\Gui\Controls\TabbedPane;
use ManiaLivePlugins\Standard\Profiler\Gui\Controls\MemoryTab;
use ManiaLivePlugins\Standard\Profiler\Gui\Controls\NetGraphTab;
use ManiaLivePlugins\Standard\Profiler\Gui\Controls\OverviewTab;
use ManiaLivePlugins\Standard\Profiler\Gui\Controls\PluginsTab;
use ManiaLivePlugins\Standard\Profiler\Gui\Controls\SpeedGraphTab;

class Stats extends \ManiaLive\Gui\ManagedWindow
{
	private static $overviewTab;
	private static $cpuGraphTab;
	private static $memoryTab;
	private static $networkGraphTab;
	private static $pluginsTab;
	
	private $tabbedPane;
	
	static function Initialize()
	{
		self::$overviewTab = new OverviewTab();
		self::$cpuGraphTab = new SpeedGraphTab();
		self::$memoryTab = new MemoryTab();
		self::$networkGraphTab = new NetGraphTab();
		self::$pluginsTab = new PluginsTab();
	}
	
	static function Clear()
	{
		self::$overviewTab->destroy();
		self::$cpuGraphTab->destroy();
		self::$memoryTab->destroy();
		self::$networkGraphTab->destroy();
		self::$pluginsTab->destroy();
		self::$overviewTab = null;
		self::$cpuGraphTab = null;
		self::$memoryTab = null;
		self::$networkGraphTab = null;
		self::$pluginsTab = null;
	}
	
	function onConstruct()
	{
		parent::onConstruct();
		$this->setTitle('Statistics');
		
		$this->tabbedPane = new TabbedPane();
		$this->tabbedPane->setPosition(1, -15);
		$this->tabbedPane->addTab(self::$overviewTab);
		$this->tabbedPane->addTab(self::$cpuGraphTab);
		$this->tabbedPane->addTab(self::$memoryTab);
		$this->tabbedPane->addTab(self::$networkGraphTab);
		$this->tabbedPane->addTab(self::$pluginsTab);
		$this->addComponent($this->tabbedPane);
	}
	
	protected function onResize($oldX, $oldY)
	{
		parent::onResize($oldX, $oldY);
		$this->tabbedPane->setSize($this->sizeX - 2, $this->sizeY - 16);
	}
}

?>