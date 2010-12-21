<?php 

namespace ManiaLive\Application;

use ManiaLive\Features\ChatCommand\Documentation;
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

set_error_handler('\ManiaLive\Application\ErrorHandling::createExcpetionFromError');

abstract class AbstractApplication extends \ManiaLive\Utilities\Singleton
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
	
	protected function __construct()
	{
		try 
		{
			// load configuration file
			$loader = Loader::getInstance();
			$loader->setConfigFilename(APP_ROOT . 'config/config.ini');
			$loader->load();
			
			// load configureation from the command line ...
			CommandLineInterpreter::read();
		
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
		Storage::getInstance();
		PluginHandler::getInstance();
		
		GuiHandler::hideAll();
		$this->connection = Connection::getInstance();
		$this->connection->enableCallbacks(true);
		
		// document all commands until here to manialive
		Documentation::getInstance()->registerCommandsFor('Core Modules');
		
		Dispatcher::dispatch(new Event($this, Event::ON_INIT));
		
		// create documentation after all plugins were loaded.
		// commands assigned on runtime are not taken into account!
		if (Loader::$config->chatcommands->createDocumentation)
			Documentation::getInstance()->create('ChatCommandList.html');
	}
	
	function run()
	{
		try
		{
			$this->init();
			Dispatcher::dispatch(new Event($this, Event::ON_RUN));
			while($this->running)
			{
				Dispatcher::dispatch(new Event($this, Event::ON_PRE_LOOP));
				// TODO Mettre ça dans des events listener?
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
		$this->running = false;
	}
	
	protected function terminate()
	{
		Dispatcher::dispatch(new Event($this, Event::ON_TERMINATE));
	}
}

?>