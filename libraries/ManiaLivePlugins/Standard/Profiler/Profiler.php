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
namespace ManiaLivePlugins\Standard\Profiler;

use ManiaLivePlugins\Standard\Version;
use ManiaLive\Gui\Windowing\WindowHandler;
use ManiaLivePlugins\Standard\Admin\Admin;
use ManiaLive\Features\Admin\AdminGroup;
use ManiaLive\Gui\Windowing\Windows\Info;
use ManiaLive\Config\Loader;
use ManiaLive\Data\Storage;
use ManiaLive\DedicatedApi\Xmlrpc\Client_Gbx;
use ManiaLib\Gui\Elements\Icons128x128_1;
use ManiaLive\Gui\Windowing\Event;
use ManiaLive\Event\Dispatcher;
use ManiaLive\Gui\Windowing\Listener;
use ManiaLivePlugins\Standard\Profiler\Gui\Windows\Stats;
use ManiaLive\DedicatedApi\Connection;

class Profiler extends \ManiaLive\PluginHandler\Plugin
{
	protected $win_stats;
	protected $time_started;

	protected $loop_start;
	protected $loop_times;
	protected $loop_count;
	protected $loop_avg;

	protected $mem_last_time;
	protected $net_last_time;

	const MODE_COUNT = 1;
	const MODE_PROFILER = 2;
	const PROFILER_LOOPS = 1000;
	const MEM_DEFAULT = 134217728;

	public static $me;

	function onInit()
	{
		$this->setRepositoryId(11);
		$this->setRepositoryVersion(2622);
	}

	function onLoad()
	{
		$this->time_started = time();
		$this->mode = false;
		$this->loop_times = array();
		$this->win_stats = array();

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

		$this->loop_start = time();
		$stats = Stats::Create($login);
		$stats->time_started = $this->time_started;
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
				if ($this->loop_count == 0)
					$this->loop_start = microtime(true);
				$this->loop_count++;
				break;

			case self::MODE_PROFILER:
				$this->loop_start = microtime(true);
				break;

			default:
		}
	}

	function onPostLoop()
	{
		switch ($this->mode)
		{
			case self::MODE_COUNT:
				if ($this->loop_start != 0 && ($diff = microtime(true) - $this->loop_start) > 1)
				{
					$wins = Stats::GetAll();
					foreach ($wins as $win_stat)
					{
						$win_stat->addCpuUsage(round($this->loop_count / $diff, 2));
						$this->loop_count = 0;

						$send_diff = Client_Gbx::$sent - $this->net_last_time[1];
						$recv_diff = Client_Gbx::$received - $this->net_last_time[0];
						$win_stat->addNetUsage($recv_diff, $send_diff);
						$this->net_last_time = array(Client_Gbx::$received, Client_Gbx::$sent);
					}
				}
				if (microtime(true) - $this->mem_last_time > 3)
				{
					$wins = Stats::GetAll();
					foreach ($wins as $win_stat)
					{
						$this->mem_last_time = microtime(true);
						$win_stat->addMemoryUsage(memory_get_usage());
					}
				}
				break;

			case self::MODE_PROFILER:

				$this->loop_count++;
				$this->loop_times[] = microtime(true) - $this->loop_start;
				if ($this->loop_count > self::PROFILER_LOOPS)
				{
					$this->loop_count = 0;

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

			$this->loop_count = 0;
			$this->loop_start = 0;
		}
	}

	function calculateStats()
	{
		// remove first time, its bad!
		array_shift($this->loop_times);

		// calculate average ...
		$this->loop_avg = array_sum($this->loop_times) / count($this->loop_times);
		$this->loop_times = array();

		$message = 'The average time for one application loop is ' . round($this->loop_avg, 5);
		Connection::getInstance()->chatSendServerMessage($message);
	}

	function onUnload()
	{
		Stats::EraseAll();
		parent::onUnload();
	}
}

?>