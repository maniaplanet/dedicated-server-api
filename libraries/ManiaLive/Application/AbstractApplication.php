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

namespace ManiaLive\Application;

use ManiaLive\Cache\Cache;
use ManiaLive\Gui\Windowing\WindowHandler;
use ManiaLive\Threading\Tools;
use ManiaLive\Threading\ThreadPool;
use ManiaLive\Utilities\Logger;
use ManiaLive\Config\Loader;
use ManiaLive\PluginHandler\PluginHandler;
use ManiaLive\Data\Storage;
use ManiaLive\DedicatedApi\Connection;
use ManiaLive\Event\Dispatcher;
use ManiaLive\Gui\Handler\GuiHandler;
use ManiaLive\Features\Tick\Ticker;

abstract class AbstractApplication extends \ManiaLib\Utils\Singleton
{
	const USLEEP_DELAY = 15000;
	/**
	 * @var bool
	 */
	protected $running = true;
	/**
	 * @var Connection
	 * @todo Connection is not the best name here. $dedicatedApi ? $api? $apiConnection ? etc.
	 */
	protected $connection;
	/**
	 * @var \ManiaLive\Cache\Cache
	 */
	protected $cache;
	
	protected function __construct()
	{
		set_error_handler('\ManiaLive\Application\ErrorHandling::createExceptionFromError');
		
		try 
		{
			$configFile = CommandLineInterpreter::preConfigLoad();
			
			// load configuration file
			$loader = Loader::getInstance();
			$loader->setConfigFilename(APP_ROOT.'config'.DIRECTORY_SEPARATOR.$configFile);
			$loader->run();
			
			// load configureation from the command line ...
			CommandLineInterpreter::postConfigLoad();
		
			// add logfile prefix ...
			$manialiveConfig = \ManiaLive\Config\Config::getInstance();
			$serverConfig = \ManiaLive\DedicatedApi\Config::getInstance();
			if ($manialiveConfig->logsPrefix != null)
			{
				$ip = str_replace('.', '-', $serverConfig->host);
				
				$manialiveConfig->logsPrefix = str_replace('%ip%',
					$ip,
					$manialiveConfig->logsPrefix);
					
				$manialiveConfig->logsPrefix = str_replace('%port%',
					$serverConfig->port,
					$manialiveConfig->logsPrefix);
			}
				
			// disable logging?
			if (!$manialiveConfig->runtimeLog)
				Logger::getLog('Runtime')->disableLog();
			
			// configure the dedicated server connection
			Connection::$hostname = $serverConfig->host;
			Connection::$port = $serverConfig->port;
			Connection::$username = $serverConfig->user;
			Connection::$password = $serverConfig->password;
		}
		catch (\Exception $e)
		{
			// exception on startup ...
			ErrorHandling::processStartupException($e);
		}
	}
	
	protected function init()
	{
		// initialize components
		new Ticker();
		
		// initialize caching
		$this->cache = Cache::getInstance();
		
		// synchronize information with dedicated server
		Storage::getInstance();
		
		// establish connection
		$this->connection = Connection::getInstance();
		
		//Initialize Chat Command Interpreter
		\ManiaLive\Features\ChatCommand\Interpreter::getInstance();
		
		// initialize plugin handler
		PluginHandler::getInstance();
		
		// enable callbacks
		$this->connection->enableCallbacks(true);
		
		// initialize threadpool
		$pool = ThreadPool::getInstance();
		
		// send config to threads
		if ($pool->getDatabase() != null)
		{
			Tools::setData($pool->getDatabase(), 'config', \ManiaLive\Config\Config::getInstance());
			Tools::setData($pool->getDatabase(), 'database', \ManiaLive\Database\Config::getInstance());
			Tools::setData($pool->getDatabase(), 'maniahome', \ManiaHome\Config::getInstance());
			Tools::setData($pool->getDatabase(), 'manialive', \ManiaLive\Application\Config::getInstance());
			Tools::setData($pool->getDatabase(), 'server', \ManiaLive\DedicatedApi\Config::getInstance());
			Tools::setData($pool->getDatabase(), 'threading', \ManiaLive\Threading\Config::getInstance());
		}
		
		// initialize windowing system
		GuiHandler::hideAll();
		WindowHandler::getInstance();
		
		Dispatcher::dispatch(new Event($this, Event::ON_INIT));
	}
	
	function run()
	{
		try
		{
			$this->init();
			
			Dispatcher::dispatch(new Event($this, Event::ON_RUN));
			ThreadPool::getInstance()->run();
			
			while($this->running)
			{
				Dispatcher::dispatch(new Event($this, Event::ON_PRE_LOOP));
				// TODO Put this into the event listener?
				$this->connection->executeCallbacks();
				GuiHandler::getInstance()->sendAll();
				$this->connection->executeMultiCall();
				Dispatcher::dispatch(new Event($this, Event::ON_POST_LOOP));
				usleep(static::USLEEP_DELAY);
			}
			$this->terminate();
		}
		catch (\Exception $e)
		{
			ErrorHandling::processRuntimeException($e);
		}
	}
	
	function kill()
	{
		$this->connection->manualFlowControlEnable(false);
		$this->running = false;
	}
	
	protected function terminate()
	{
		Dispatcher::dispatch(new Event($this, Event::ON_TERMINATE));
	}
}

?>