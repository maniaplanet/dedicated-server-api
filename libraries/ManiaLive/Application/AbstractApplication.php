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
use ManiaLive\Config\Config;
use ManiaLive\Config\Loader;
use ManiaLive\PluginHandler\PluginHandler;
use ManiaLive\Data\Storage;
use ManiaLive\Utilities\Console;
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
			$loader->setConfigFilename(APP_ROOT . 'config/'.$configFile);
			$loader->load();
			
			// load configureation from the command line ...
			CommandLineInterpreter::postConfigLoad();
		
			// add logfile prefix ...
			if (Loader::$config->logsPrefix != null)
			{
				$ip = str_replace('.', '-', Loader::$config->server->host);
				
				Loader::$config->logsPrefix = str_replace('%ip%',
					$ip,
					Loader::$config->logsPrefix);
					
				Loader::$config->logsPrefix = str_replace('%port%',
					Loader::$config->server->port,
					Loader::$config->logsPrefix);
			}
				
			// disable logging?
			if (!Loader::$config->runtimeLog)
				Logger::getLog('Runtime')->disableLog();
			
			// configure the dedicated server connection
			Connection::$hostname = Loader::$config->server->host;
			Connection::$port = Loader::$config->server->port;
			Connection::$username = 'SuperAdmin';
			Connection::$password = Loader::$config->server->password;
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
			Tools::setData($pool->getDatabase(), 'config', Loader::$config);
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