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

use ManiaLib\Gui\Elements\Label;
use ManiaLive\Database\Connection;
use ManiaLive\DedicatedApi\Xmlrpc\Client;
use ManiaLive\Gui\GuiHandler;
use ManiaLive\PluginHandler\PluginHandler;
use ManiaLive\Threading\ThreadHandler;
use ManiaLive\Event\Dispatcher;
use ManiaLivePlugins\Standard\Profiler\Listener as MonitorListener;
use ManiaLivePlugins\Standard\Profiler\Event as MonitorEvent;
use ManiaLivePlugins\Standard\Profiler\Profiler;

class OverviewTab extends \ManiaLive\Gui\Controls\Tabbable implements MonitorListener
{
	private $leftLabel;
	private $rightLabel;
	
	private $memoryLimit;
	private $cpuStats;
	
	function __construct()
	{
		$this->setTitle('Overview');
		
		$this->memoryLimit = str_replace('M', 1024*1024, ini_get('memory_limit'));
		if($this->memoryLimit < 0)
			$this->memoryLimit = Profiler::MEM_DEFAULT;
		
		$this->leftLabel = new Label();
		$this->leftLabel->setStyle(Label::TextCardSmallScores2Rank);
		$this->leftLabel->enableAutonewline();
		$this->leftLabel->setPosition(1, -1);
		$this->addComponent($this->leftLabel);
		
		$this->rightLabel = new Label();
		$this->rightLabel->setStyle(Label::TextCardSmallScores2Rank);
		$this->rightLabel->enableAutonewline();
		$this->addComponent($this->rightLabel);
		
		Dispatcher::register(MonitorEvent::getClass(), $this, MonitorEvent::ON_NEW_CPU_VALUE);
	}
	
	protected function onResize($oldX, $oldY)
	{
		$this->leftLabel->setSize($this->sizeX / 2 - 2, $this->sizeY - 2);
		$this->rightLabel->setSize($this->sizeX / 2 - 2, $this->sizeY - 2);	
		$this->rightLabel->setPosition($this->sizeX / 2 + 1, -1);
	}
	
	function onDraw()
	{
		// statistics for memory usage
		$memory = memory_get_usage();
		$text = '$oPHP Memory$z'."\n"
				.'Current Memory Usage: '.round($memory / 1024)." kb\n"
				.'Total Peak Memory: '.round(memory_get_peak_usage() / 1024)." kb\n"
				.'PHP Memory Limit: '.round($this->memoryLimit / 1024)." kb\n"
				.'Amount Used: '.round(100 * $memory / $this->memoryLimit, 2)."%\n"
		
		// statistics for cpu usage (speed)
				.'$oPHP Speed$z'."\n";
		if(empty($this->cpuStats))
			$text .= '$iRetrieving information...$z'."\n";
		else
			$text .= 'Current Cycles per Second: '.end($this->cpuStats)."\n"
					.'Avg Reaction Time: '.round(1000 * count($this->cpuStats) / (array_sum($this->cpuStats) ?: 1))." msecs\n";
		
		// manialive specific stats
		$text .= '$oManiaLive$z'."\n";
		
		// runtime
		$diff = time() - \ManiaLive\Application\AbstractApplication::$startTime;
		$seconds = $diff % 60;
		$minutes = floor($diff % 3600 / 60);
		$hours = floor($diff % 86400 / 3600);
		$days = floor($diff / 86400);
		$text .= "ManiaLive Uptime:\n$days days and $hours hours\n$minutes min and $seconds seconds\n";
		
		// threading
		$text .= '$oThreading$z'."\n";
		$processHandler = ThreadHandler::getInstance();
		if($processHandler->isEnabled())
			$text .= 'Enabled; Running:'.$processHandler->countThreads().'; Restarted:'.$processHandler->countRestartedThreads()."\n"
					.$processHandler->countFinishedCommands().' commands finished at avg '.round($processHandler->getAverageResponseTime(), 3).' sec';
		else
			$text .= "Disabled\n";
		
		// update left side of the page
		$this->leftLabel->setText($text);
		
		// database
		$text = '$oDatabase$z'."\n";
		
		$times = Connection::getMeasuredAverageTimes();
		if(count($times))
		{
			$i = 0;
			foreach($times as $time)
				$text .= 'Connection #'.++$i.":\n"
						.'Avg Query Time: '.round($time, 3).' sec'."\n";
		}
		else
			$text .= "No Database connections running.\n";
		
		// network
		$text .= '$oNetwork$z'."\n"
				.'Total Bytes Sent: '.round(Client::$sent / 1024)."kb\n"
				.'Total Bytes Received: '.round(Client::$received / 1024)."kb\n"
		
		// graphical user interface
				.'$oInterface Drawing$z'."\n"
				.'Avg Drawing Time: ' . round(GuiHandler::getInstance()->getAverageSendingTimes() * 1000) . " msec\n"
		
		// plugin handler
				.'$oPlugins$z'."\n"
				.'Currently loaded: '.count(PluginHandler::getInstance()->getLoadedPluginsList());
		
		// update right side of the page
		$this->rightLabel->setText($text);
	}
	
	function onNewCpuValue($newValue)
	{
		$this->cpuStats[] = $newValue;
		if(count($this->cpuStats) > 10)
			array_shift($this->cpuStats);
		
		$this->redraw();
	}
	
	function onNewMemoryValue($newValue) {}
	function onNewNetworkValue($newValue) {}
	
	function destroy()
	{
		Dispatcher::unregister(MonitorEvent::getClass(), $this);
		unset($this->cpuStats);
		parent::destroy();
	}
}

?>