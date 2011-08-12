<?php

namespace ManiaLivePlugins\Standard\Profiler\Gui\Controls;

use ManiaLive\Gui\Windowing\Controls\Pager;

use ManiaLive\PluginHandler\PluginHandler;

use ManiaLivePlugins\Standard\Profiler\Profiler;
use ManiaLive\Gui\Handler\GuiHandler;
use ManiaLive\DedicatedApi\Xmlrpc\Client;
use ManiaLive\Database\Connection;
use ManiaLive\Threading\Commands\Command;
use ManiaLive\Threading\ThreadPool;
use ManiaLib\Gui\Elements\Label;

class OverviewTab extends \ManiaLive\Gui\Windowing\Controls\Tab
{
	protected $lbl_left;
	protected $lbl_right;
	
	public $mem_stats;
	public $cpu_stats;
	public $time_started;
	protected $php_limit;
	
	function initializeComponents()
	{
		$this->php_limit = str_replace('M', 1024*1024, ini_get('memory_limit'));
		if ($this->php_limit < 0) $this->php_limit = Profiler::MEM_DEFAULT;
		
		$this->lbl_left = new Label();
		$this->lbl_left->enableAutonewline();
		$this->lbl_left->setPosition(1, 1);
		$this->addComponent($this->lbl_left);
		
		$this->lbl_right = new Label();
		$this->lbl_right->enableAutonewline();
		$this->addComponent($this->lbl_right);
	}
	
	function onResize()
	{
		$this->lbl_left->setSize($this->getSizeX() / 2 - 2, $this->getSizeY() - 2);
		
		$this->lbl_right->setPosition($this->getSizeX() / 2 + 1, 1);
		$this->lbl_right->setSize($this->getSizeX() / 2 - 2, $this->getSizeY() - 2);	
	}
	
	function beforeDraw()
	{
		// if there are not enough information yet, then wait for next redraw
		if (empty($this->cpu_stats))
		{
			$this->lbl_left->setText('Retrieving information ...');
			return;
		}
		
		// statistics for memory usage
		$mem = memory_get_usage();
		$text = '$oPHP Memory$z'."\n";
		$text .= 'Current Memory Usage: ' . round($mem / 1024) . ' kb' . "\n";
		$text .= 'Total Peak Memory: ' . round(memory_get_peak_usage() / 1024) . ' kb' . "\n";
		$text .= 'PHP Memory Limit: ' . $this->php_limit . " kb\n";
		$text .= 'Amount Used: ' . round(100 / $this->php_limit * $mem, 2) . "%\n";
		
		// statistics for cpu usage (speed)
		$text .= '$oPHP Speed$z'."\n";
		$text .= 'Current Cycles per Second: ' . end($this->cpu_stats) . "\n";
		$text .= 'Avg Reaction Time: ' . round((1 / (max(1, array_sum($this->cpu_stats)) / max(1, count($this->cpu_stats)))) * 1000) . ' msecs' . "\n";
		
		// manialive specific stats
		$text .= '$oManiaLive$z'."\n";
		
		// runtime
		$diff = time() - $this->time_started;
		$seconds = $diff % 60;
		$minutes = floor($diff % 3600 / 60);
		$hours = floor($diff % 86400 / 3600);
		$days = floor($diff / 86400);
		$text .= "ManiaLive Uptime:\n$days days and $hours hours\n$minutes min and $seconds seconds\n";
		
		// threading
		$text .= '$oThreading$z'."\n";
		if (ThreadPool::$threadingEnabled)
		{
			$text .= "Enabled; ";
			$text .= 'Running:' . ThreadPool::getInstance()->getThreadCount().'; ';
			$text .= 'Restarted:' . ThreadPool::$threadsDiedCount . "\n";
			$text .= Command::getTotalCommands() . " commands finished at avg " . round(ThreadPool::$avgResponseTime, 3) . " sec";
		}
		else
		{
			$text .= "Threading: Disabled\n";
		}
		
		// update left side of the page
		$this->lbl_left->setText($text);
		
		// database
		$text = '$oDatabase$z'."\n";
		
		$times = Connection::getMeasuredAvgTimes();
		if (count($times) == 0)
		{
			$text .= "No Database connections running.\n";
		}
		else
		{
			$i = 1;
			foreach ($times as $time)
			{
				$text .= 'Connection #' . $i++ . ":\n";
				$text .= 'Avg Query Time: ' . round($time, 4) . ' sec' . "\n";
			}
		}
		
		// database
		$text .= '$oNetwork$z'."\n";
		$text .= 'Total Bytes Sent: ' . round(Client::$sent/1024) . "kb\n";
		$text .= 'Total Bytes Received: ' . round(Client::$received/1024) . "kb\n";
		
		// graphical user interface
		$text .= '$oInterface Drawing$z'."\n";
		$text .= 'Avg Drawing Time: ' . round(GuiHandler::$avgSendall * 1000) . " msec\n";
		
		// plugin handler
		$text .= '$oPlugins$z'."\n";
		$text .= 'Currently loaded: '.count(PluginHandler::getInstance()->getLoadedPluginsList());
		
		// update right side of the page
		$this->lbl_right->setText($text);
	}
	
	function destroy()
	{
		unset($this->mem_stats);
		unset($this->cpu_stats);
		parent::destroy();
	}
}

?>