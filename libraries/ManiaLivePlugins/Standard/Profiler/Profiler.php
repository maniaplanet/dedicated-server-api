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

namespace ManiaLivePlugins\Standard\Profiler;

use ManiaLib\Gui\Elements\Icons128x128_1;
use ManiaLive\Application\Event as AppEvent;
use ManiaLive\DedicatedApi\Xmlrpc\Client;
use ManiaLive\Features\Admin\AdminGroup;
use ManiaLive\Gui\Windows\Info;

use ManiaLivePlugins\Standard\Profiler\Gui\Windows\Stats;

class Profiler extends \ManiaLive\PluginHandler\Plugin
{
	private $isProfiling;
	private $askingPlayers;
	private $loopStart;
	private $loopTimes;
	private $loopCount;
	
	const PROFILER_LOOPS = 1000;
	const MEM_DEFAULT = 0x8000000; // 128 MB
	
	public static $me;
	
	function onInit()
	{
		$this->setVersion('0.5');
	}
	
	function onLoad()
	{
		$this->isProfiling = false;
		$this->askingPlayers = array();
		
		$this->enablePluginEvents();
		
		$cmd = $this->registerChatCommand('profile', 'startProfiler', 0, true, AdminGroup::get());
		$cmd->isPublic = false;
		$cmd->help = 'checks the average duration of one application loop.';
		
		$cmd = $this->registerChatCommand('stats', 'showStats', 0, true, AdminGroup::get());
		$cmd->isPublic = false;
		$cmd->help = 'shows statistics on how the application performs.';
		
		if($this->isPluginLoaded('Standard\Menubar'))
			$this->onPluginLoaded('Standard\Menubar');
		
		self::$me = $this;
	}
	
	function onReady()
	{
		Monitor::getInstance()->start();
		Stats::Initialize();
	}
	
	function onPluginLoaded($pluginId)
	{
		if($pluginId == 'Standard\Menubar')
		{
			// set menu icon for dedimanias menu ...
			$this->callPublicMethod('Standard\Menubar', 'initMenu', Icons128x128_1::Statistics);
			
			// add button for records window ...
			$this->callPublicMethod('Standard\Menubar',
				'addButton',
				'Live Statistics',
				array($this, 'showStats'),
				true);
		}
	}
	
	function showStats($login)
	{
		$stats = Stats::Create($login);
		$stats->setSize(180, 97);
		$stats->centerOnScreen();
		$stats->show();
	}
	
	function startProfiler($login)
	{
		if($this->isProfiling)
			$this->connection->chatSendServerMessage('> Profiling already started, waiting for result!', $login);
		else
		{
			$this->isProfiling = true;
			$this->loopCount = 0;
			$this->loopTimes = array();
			$this->connection->chatSendServerMessage('> Profiling started, this may take a while!', $login);
			$this->enableApplicationEvents(AppEvent::ON_PRE_LOOP | AppEvent::ON_POST_LOOP);
		}
		$this->askingPlayers[] = $login;
	}
	
	function onPreLoop()
	{
		if($this->isProfiling)
			$this->loopStart = microtime(true);
	}
	
	function onPostLoop()
	{
		if($this->isProfiling)
		{
			$this->loopTimes[] = microtime(true) - $this->loopStart;
			if(++$this->loopCount > self::PROFILER_LOOPS)
			{
				array_shift($this->loopTimes);
				$this->connection->chatSendServerMessage(
						'> The average time for one application loop is '.round(1000 * array_sum($this->loopTimes) / count($this->loopTimes), 3).'ms',
						array_unique($this->askingPlayers));
				$this->isProfiling = false;
				$this->askingPlayers = array();
				$this->disableApplicationEvents();
			}
		}
	}
	
	function onUnload()
	{
		Monitor::getInstance()->stop();
		Stats::EraseAll();
		Stats::Clear();
		parent::onUnload();
	}
}

?>