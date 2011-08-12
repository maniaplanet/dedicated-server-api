<?php

namespace ManiaLivePlugins\Standard\Profiler;

use ManiaLivePlugins\Standard\Version;
use ManiaLive\Gui\Windowing\WindowHandler;
use ManiaLivePlugins\Standard\Admin\Admin;
use ManiaLive\Features\Admin\AdminGroup;
use ManiaLive\Gui\Windowing\Windows\Info;
use ManiaLive\Config\Loader;
use ManiaLive\Data\Storage;
use ManiaLive\DedicatedApi\Xmlrpc\Client;
use ManiaLib\Gui\Elements\Icons128x128_1;
use ManiaLive\Gui\Windowing\Event;
use ManiaLive\Event\Dispatcher;
use ManiaLive\Gui\Windowing\Listener;
use ManiaLivePlugins\Standard\Profiler\Gui\Windows\Stats;
use ManiaLive\DedicatedApi\Connection;

class Profiler extends \ManiaLive\PluginHandler\Plugin
{
	protected $winStats;
	protected $timeStarted;
	
	protected $loopStart;
	protected $loopTimes;
	protected $loopCount;
	protected $loopAvg;
	
	protected $memLastTime;
	protected $netLastTime;
	
	const MODE_COUNT = 1;
	const MODE_PROFILER = 2;
	const PROFILER_LOOPS = 1000;
	const MEM_DEFAULT = 134217728;
	
	public static $me;
	
	function onInit()
	{
	}
	
	function onLoad()
	{		
		$this->timeStarted = time();
		$this->mode = false;
		$this->loopTimes = array();
		$this->winStats = array();
		
		$this->enablePluginEvents();
		
		$cmd = $this->registerChatCommand('profile', 'startProfiler', 0, true);
		$cmd->isPublic = false;
		$cmd->help = 'checks the average duration of one application loop.';
		
		$cmd = $this->registerChatCommand('stats', 'showStats', 0, true);
		$cmd->isPublic = false;
		$cmd->help = 'shows statistics on how the application performs.';
		
		$this->buildMenu();
		
		self::$me = $this;
	}
	
	protected function buildMenu()
	{
		if ($this->isPluginLoaded('Standard\Menubar'))
		{
			// set menu icon for dedimanias menu ...
			$this->callPublicMethod('Standard\Menubar',
				'initMenu',
				Icons128x128_1::Statistics);
			
			// add button for records window ...
			$this->callPublicMethod('Standard\Menubar',
				'addButton',
				'Live Statistics',
				array($this, 'showStats'),
				true);
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/PluginHandler/ManiaLive\PluginHandler.Plugin::onPluginLoaded()
	 */
	function onPluginLoaded($pluginId)
	{
		if ($pluginId == 'Standard\Menubar')
		{
			$this->buildMenu();
		}
	}
	
	function showStats($login)
	{
		if (!AdminGroup::contains($login))
		{
			$win = Info::Create($login);
			$win->setSize(40, 15);
			$win->setTitle('No Permission');
			$win->setText('The command you have been trying to use is preserved to Administrators!');
			$win->centerOnScreen();
			WindowHandler::showDialog($win);
			return;
		}
		
		$this->mode = self::MODE_COUNT;
		
		$this->loopStart = time();
		$stats = Stats::Create($login);
		$stats->time_started = $this->timeStarted;
		$stats->setSize(72, 51.5);
		$stats->centerOnScreen();
		$stats->show();
		
		// execute profiling on pre and postloop ...
		$this->enableApplicationEvents();
		
		$this->enableWindowingEvents();
	}
	
	function startProfiler($login)
	{
		if (!AdminGroup::contains($login))
			return Connection::getInstance()->chatSendServerMessage('$f00This command is preserved to Administrators only!');
		
		$this->mode = self::MODE_PROFILER;
		
		// print notification
		Connection::getInstance()->chatSendServerMessage('> Profiling started, this may take a while!', Storage::getInstance()->players[$login]);
		
		// profile on pre and postloop
		$this->enableApplicationEvents();
	}
	
	function onPreLoop()
	{
		switch ($this->mode)
		{
			case self::MODE_COUNT:
				if ($this->loopCount == 0)
					$this->loopStart = microtime(true);
				$this->loopCount++;
				break;
				
			case self::MODE_PROFILER:
				$this->loopStart = microtime(true);
				break;
				
			default:
		}
	}
	
	function onPostLoop()
	{
		switch ($this->mode)
		{
			case self::MODE_COUNT:
				if ($this->loopStart != 0 && ($diff = microtime(true) - $this->loopStart) > 1)
				{
					$wins = Stats::GetAll();
					foreach ($wins as $win_stat)
					{
						$win_stat->addCpuUsage(round($this->loopCount / $diff, 2));
						$this->loopCount = 0;
						
						$send_diff = Client::$sent - $this->netLastTime[1];
						$recv_diff = Client::$received - $this->netLastTime[0];
						$win_stat->addNetUsage($recv_diff, $send_diff);
						$this->netLastTime = array(Client::$received, Client::$sent);
					}
				}
				if (microtime(true) - $this->memLastTime > 3)
				{
					$wins = Stats::GetAll();
					foreach ($wins as $win_stat)
					{
						$this->memLastTime = microtime(true);
						$win_stat->addMemoryUsage(memory_get_usage());
					}
				}
				break;
				
			case self::MODE_PROFILER:
				
				$this->loopCount++;
				$this->loopTimes[] = microtime(true) - $this->loopStart;
				if ($this->loopCount > self::PROFILER_LOOPS)
				{
					$this->loopCount = 0;
					
					// calculate average loop time
					$this->calculateStats();
					
					// stop profiling
					$this->disableApplicationEvents();
				}
				break;
				
			default:
		}
	}
	
	function onWindowRecover($login, $window)
	{
		if (Stats::$open > 0)
		{	
			// execute profiling on pre and postloop ...
			$this->enableApplicationEvents();
		}
	}
	
	function onWindowClose($login, $window)
	{
		if (Stats::$open == 0)
		{
			// dont execute onloop events anymore
			$this->disableApplicationEvents();

			$this->loopCount = 0;
			$this->loopStart = 0;
		}
	}
	
	function calculateStats()
	{
		// remove first time, its bad!
		array_shift($this->loopTimes);
		
		// calculate average ...
		$this->loopAvg = array_sum($this->loopTimes) / count($this->loopTimes);
		$this->loopTimes = array();
		
		$message = 'The average time for one application loop is ' . round($this->loopAvg, 5);
		Connection::getInstance()->chatSendServerMessage($message);
	}
	
	function onUnload()
	{
		Stats::EraseAll();
		parent::onUnload();
	}
}

?>