<?php

namespace ManiaLive\PluginHandler;

use ManiaLive\Utilities\Logger;
use ManiaLive\Application\FatalException;
use ManiaLive\Config\Loader;
use ManiaLive\Event\Dispatcher;
use ManiaLive\Data\Storage;
use ManiaLive\Features\ChatCommand\Interpreter;
use ManiaLive\Features\ChatCommand\Command;
use ManiaLive\Gui\Toolkit\Cards\Dialog;
use ManiaLive\Gui\Displayables\Blank;
use ManiaLive\Gui\Toolkit\Elements\Bgs1;
use ManiaLive\Gui\Displayables\Advanced;
use ManiaLive\Gui\Handler\GuiHandler;
use ManiaLive\Database\Connection as DbConnection;
use ManiaLive\Utilities\Console as Console;
use ManiaLive\GuiHandler\GuiToolkit;
use ManiaLive\DedicatedApi\Connection;

/**
 * Extend this class to create a Plugin that can be used with the
 * PluginHandler.
 * This will also provide function shortcuts for registering chat commands,
 * dependency handling and the possibility of Plugin communication.
 * To have a Plugin loaded, just attach it to the pluginhandler.xml which is
 * located in the config folder.
 * 
 * @author Florian Schnell
 * @copyright 2010 NADEO
 */
abstract class Plugin extends \ManiaLive\DedicatedApi\Callback\Adapter
	implements \ManiaLive\Threading\Listener,
	\ManiaLive\Gui\Windowing\Listener,
	\ManiaLive\Features\Tick\Listener,
	\ManiaLive\Application\Listener,
	\ManiaLive\Data\Listener
{
	/**
	 * @var string
	 */
	private $name;
	/**
	 * @var string
	 */
	private $author;
	/**
	 * @var integer
	 */
	private $version;
	/**
	 * @var array[Dependency]
	 */
	private $dependencies;
	/**
	 * Event subscriber swichtes
	 */
	private $events_application;
	private $events_threading;
	private $events_windowing;
	private $events_tick;
	private $events_server;
	private $events_storage;
	/**
	 * @var \ManiaLive\DedicatedApi\Connection
	 */
	protected $connection;
	/**
	 * @var ManiaLive\PluginHandler\PluginHandler
	 */
	private $plugin_handler;
	/**
	 * @var array[\ReflectionMethod]
	 */
	private $methods;
	/**
	 * @var ManiaLive\Data\Storage
	 */
	protected $storage;
	/**
	 * @var array
	 */
	private $settings;
	/**
	 * @var \ManiaLive\Threading\ThreadPool
	 */
	private $threadpool;
	/**
	 * @var integer
	 */
	private $threadId;
	
	final function __construct($plugin_id)
	{
		$this->settings = array();
		$this->events_application = false;
		$this->events_threading = false;
		$this->events_tick = false;
		$this->events_windowing = false;
		
		$this->dependencies = array();
		$this->methods = array();
		
		$class_path = get_class($this);
		$items = explode('\\', $class_path);

		$this->id = $plugin_id;
		$this->name = $items[count($items)-2];
		$this->author = $items[count($items)-3];
		$this->setVersion(1);
		
		$this->connection = Connection::getInstance();
		$this->plugin_handler = PluginHandler::getInstance();
		$this->storage = Storage::getInstance();
		$this->threadPool = \ManiaLive\Threading\ThreadPool::getInstance();
		$this->threadId = false;
	}
	
	/**
	 * Sets the current version number for this Plugin.
	 * Can only be used during initialization!
	 * @param integer $version
	 * @throws \InvalidArgumentException
	 */
	final protected function setVersion($version)
	{
		if (!is_numeric($version))
			throw new \InvalidArgumentException('Version number is expected to be numeric!');
		$this->version = $version;
	}
	
	/**
	 * Returns the version number of the Plugin.
	 * @return integer
	 */
	final public function getVersion()
	{
		return $this->version;
	}
	
	/**
	 * Returns the name of the Plugin.
	 * @return string
	 */
	final public function getName()
	{
		return $this->name;
	}
	
	/**
	 * Returns author\name combination for identification.
	 * @return string
	 */
	final public function getId()
	{
		return $this->id;
	}
	
	/**
	 * Returns the author of the Plugin.
	 * @return string
	 */
	final public function getAuthor()
	{
		return $this->author;
	}
	
	/**
	 * Adds a Dependency to the Plugin.
	 * Can only be used during initialisation!
	 * @param ManiaLive\PluginHandler\Dependency $dependency
	 */
	final public function addDependency(Dependency $dependency)
	{
		$this->dependencies[] = $dependency;
	}
	
	/**
	 * Returns an array of all known dependencies of this Plugin.
	 * @return array[ManiaLive\PluginHandler\Dependency]
	 */
	final public function getDependencies()
	{
		return $this->dependencies;
	}
	
	/**
	 * Declare this method as public.
	 * It then can be called by other Plugins.
	 * @param string $name The name of the method you want to expose.
	 * @throws Exception
	 */
	final protected function setPublicMethod($name)
	{
		try 
		{
			$method = new \ReflectionMethod($this, $name);
			
			if (!$method->isPublic())
				throw new Exception('The method "'.$name.'" must be declared as public!');
			
			$this->methods[$name] = $method;
		}
		catch (\ReflectionException $ex)
		{
			throw new Exception('The method "'.$name.'" does not exist and therefor can not be exposed!');
		}
	}
	
	/**
	 * Calls a public method of the specified plugin.
	 * The method has been marked as public by the owner.
	 * The plugin has to be registered at the plugin handler.
	 * @param string $plugin_name
	 * @param string $method_name
	 */
	final protected function callPublicMethod($plugin_id, $method_name)
	{
		$this->restrictIfUnloaded();
		$args = func_get_args();
		array_shift($args);
		array_shift($args);
		return $this->plugin_handler->callPublicMethod($this, $plugin_id, $method_name, $args);
	}
	
	/**
	 * Gets a method, that has been marked as public, from this Plugin.
	 * This method will be invoked by the Plugin Handler.
	 * If you want to call a method from another Plugin, then use the internal callPublicMethod function.
	 * @param \ReflectionMethod $method_name
	 * @throws Exception
	 */
	final public function getPublicMethod($method_name)
	{
		if (isset($this->methods[$method_name]))
		{
			return $this->methods[$method_name];
		}
		else
		{
			throw new Exception("The method '$method_name' does not exist or has not been set public for plugin '{$this->name}'!");
		}
	}
	
	/**
	 * Returns a list of the commands that are exposed by this plugin.
	 * @return array An array with the keys: name, parameter_count, parameters
	 */
	final public function getPublicMethods()
	{
		$methods = array();
		foreach ($this->methods as $name => $method)
		{
			$info = array
			(
				'name' => $name,
				'parameter_count' => $method->getNumberOfParameters(),
				'parameters' => array()
			);
			
			$parameters = $method->getParameters();
			foreach ($parameters as $parameter)
			{
				if ($parameter->allowsNull())
					$info['parameters'][] = '['.$parameter->name.']';
				else
					$info['parameters'][] = $parameter->name;
			}
			
			$methods[] = $info;
		}
		return $methods;
	}
	
	/**
	 * This method can be used to restrict a call to a specific method
	 * until the plugin has been loaded successfully!
	 * @throws \Exception
	 */
	final private function restrictIfUnloaded()
	{
		if (!$this->isLoaded())
		{
			$trace = debug_backtrace();
			throw new \Exception("The method '{$trace[1]['function']}' can not be called before the Plugin '" . $this->getId() . "' has been loaded!");
		}
	}
	
	/**
	 * Checks whether the current plugin has been loaded.
	 * @return bool
	 */
	final public function isLoaded()
	{
		return $this->isPluginLoaded($this->getId());
	}
	
	/**
	 * Is the plugin currently loaded or not?
	 * @param string $name
	 * @return bool
	 */
	final public function isPluginLoaded($plugin_id, $min = Dependency::NO_LIMIT, $max = Dependency::NO_LIMIT)
	{
		return $this->plugin_handler->isPluginLoaded($plugin_id, $min, $max);
	}
	
	// Helpers
	
	/**
	 * Start invoking methods for application intern events which are
	 * onInit, onRun, onPreLoop, onPostLoop, onTerminate
	 */
	final function enableApplicationEvents()
	{
		$this->restrictIfUnloaded();
		if (!$this->events_application)
			Dispatcher::register(\ManiaLive\Application\Event::getClass(), $this);
		$this->events_application = true;
	}
	
	/**
	 * Stop listening for application events.
	 */
	final function disableApplicationEvents()
	{
		$this->restrictIfUnloaded();
		Dispatcher::unregister(\ManiaLive\Application\Event::getClass(), $this);
		$this->events_application = false;
	}
	
	/**
	 * Start invoking the ticker method (onTick) every second.
	 */
	final function enableTickerEvent()
	{
		$this->restrictIfUnloaded();
		if (!$this->events_tick)
			Dispatcher::register(\ManiaLive\Features\Tick\Event::getClass(), $this);
		$this->events_tick = true;
	}
	
	/**
	 * Stop listening for the ticker event.
	 */
	final function disableTickerEvent()
	{
		$this->restrictIfUnloaded();
		Dispatcher::unregister(\ManiaLive\Features\Tick\Event::getClass(), $this);
		$this->events_tick = false;
	}
	
	/**
	 * Start invoking methods for extern dedicated server events which are
	 * the callbacks described in the ListCallbacks.html which you have retrieved with your
	 * dedicated server.
	 * Otherwise you can find an online copy here http://server.xaseco.org/callbacks.php
	 */
	final function enableDedicatedEvents()
	{
		$this->restrictIfUnloaded();
		if (!$this->events_server)
			Dispatcher::register(\ManiaLive\DedicatedApi\Callback\Event::getClass(), $this);
		$this->events_server = true;
	}
	
	/**
	 * Stop listening for dedicated server events.
	 */
	final function disableDedicatedEvents()
	{
		$this->restrictIfUnloaded();
		Dispatcher::unregister(\ManiaLive\DedicatedApi\Callback\Event::getClass(), $this);
		$this->events_server = false;
	}
	
	/**
	 * Start listening for Storage events:
	 * onPlayerNewBestTime, onPlayerNewRank, onPlayerNewBestScore.
	 */
	final function enableStorageEvents()
	{
		$this->restrictIfUnloaded();
		if (!$this->events_storage)
			Dispatcher::register(\ManiaLive\Data\Event::getClass(), $this);
		$this->events_storage = true;
	}
	
	/**
	 * Stop listening for Storage Events.
	 */
	final function disableStorageEvents()
	{
		$this->restrictIfUnloaded();
		Dispatcher::unregister(\ManiaLive\Data\Event::getClass(), $this);
		$this->events_storage = false;
	}
	
	/**
	 * Starts to listen for Window events like:
	 * onWindowClose
	 */
	final function enableWindowingEvents()
	{
		$this->restrictIfUnloaded();
		if (!$this->events_windowing)
			Dispatcher::register(\ManiaLive\Gui\Windowing\Event::getClass(), $this);
		$this->events_windowing = true;
	}
	
	/**
	 * Stop listening for Window events.
	 */
	final function disableWindowingEvents()
	{
		$this->restrictIfUnloaded();
		Dispatcher::unregister(\ManiaLive\Gui\Windowing\Event::getClass(), $this);
		$this->events_windowing = false;
	}
	
	/**
	 * Starts listening for threading events like:
	 * onThreadStart, onThreadRestart, onThreadDies, onThreadTimeOut
	 */
	final function enableThreadingEvents()
	{
		$this->restrictIfUnloaded();
		if (!$this->events_threading)
			Dispatcher::register(\ManiaLive\Threading\Event::getClass(), $this);
		$this->events_threading = true;
	}
	
	/**
	 * Stop listening for threading events.
	 */
	final function disableThreadingEvents()
	{
		$this->restrictIfUnloaded();
		Dispatcher::unregister(\ManiaLive\Threading\Event::getClass(), $this);
		$this->events_threading = false;
	}
	
	/**
	 * Creates a new Thread.
	 * @return integer
	 */
	protected function createThread()
	{
		if ($this->threadId === false)
			$this->threadId = $this->threadPool->createThread();
		else
			return false;
		return $this->threadId;
	}
	
	/**
	 * Assigns work only to the thread that has been created by this plugin.
	 * @param \ManiaLive\Threading\Runnable $work
	 */
	protected function sendWorkToOwnThread(\ManiaLive\Threading\Runnable $work, $callback = null)
	{
		if ($callback != null) $callback = array($this, $callback);
		if ($this->threadId !== false)
			$this->threadPool->addCommand(new \ManiaLive\Threading\Commands\RunCommand($work, $callback), $this->threadId);
	}
	
	/**
	 * Assign work to a thread.
	 * @param \ManiaLive\Threading\Runnable $work
	 */
	protected function sendWorkToThread(\ManiaLive\Threading\Runnable $work, $callback = null)
	{
		if ($callback != null) $callback = array($this, $callback);
		if ($this->threadPool->getThreadCount() > 0)
			$this->threadPool->addCommand(new \ManiaLive\Threading\Commands\RunCommand($work, $callback));
	}
	
	/**
	 * Registers a chatcommand at the Interpreter.
	 * @param string $command_name
	 * @param integer $parameter_count
	 * @param string $callback_method
	 * @param bool $add_login
	 * @param array[string] $authorizedLogin
	 * @return \ManiaLive\Features\ChatCommand\Command
	 */
	final function registerChatCommand($command_name, $callback_method, $parameter_count = 0, $add_login = false, $authorizedLogin = array())
	{
		$this->restrictIfUnloaded();
		$cmd = new Command($command_name, $parameter_count, $authorizedLogin);
		$cmd->callback = array($this, $callback_method);
		$cmd->addLoginAsFirstParameter = $add_login;
		$cmd->isPublic = true;
		Interpreter::getInstance()->register($cmd);
		return $cmd;
	}
	
	/**
	 * Write message into the plugin's logfile.
	 * Prefix with Plugin's name.
	 * @param string $text
	 */
	final protected function writeLog($text)
	{
		Logger::getLog($this->author . '' . $this->name)->write($text);
	}
	
	/**
	 * Write message onto the commandline.
	 * Prefix with Plugin's name and 
	 * @param string $text
	 */
	final protected function writeConsole($text)
	{
		Console::println('[' . Console::getDatestamp() . '|' . $this->name . '] ' . $text);
	}
	
	// LISTENERS
	
	// plugin events ...
	
	function onInit() {}
	
	function onLoad() {}
	
	function onReady() {}
	
	// application events ...
	
	function onRun() {}
	
	function onPreLoop() {}
	
	function onPostLoop() {}
	
	function onTerminate() {}
	
	// dedicated callbacks
	
	function onPlayerConnect($login, $isSpectator) {}
	
	function onPlayerDisconnect($login) {}
	
	function onPlayerChat($playerUid, $login, $text, $isRegistredCmd) {}
	
	function onPlayerManialinkPageAnswer($playerUid, $login, $answer) {}
	
	function onEcho($internal, $public) {}
	
	function onServerStart() {}
	
	function onServerStop() {}
	
	function onBeginRace($challenge) {}
	
	function onEndRace($rankings, $challenge) {}
	
	function onBeginChallenge($challenge, $warmUp, $matchContinuation) {}
	
	function onEndChallenge($rankings, $challenge, $wasWarmUp, $matchContinuesOnNextChallenge, $restartChallenge) {}
	
	function onBeginRound() {}
	
	function onEndRound() {}
	
	function onStatusChanged($statusCode, $statusName) {}
	
	function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex) {}
	
	function onPlayerFinish($playerUid, $login, $timeOrScore) {}
	
	function onPlayerIncoherence($playerUid, $login) {} 

	function onBillUpdated($billId, $state, $stateName, $transactionId) {}

	function onTunnelDataReceived($playerUid, $login, $data) {} 

	function onChallengeListModified($curChallengeIndex, $nextChallengeIndex, $isListModified) {} 

	function onPlayerInfoChanged($playerInfo) {}

	function onManualFlowControlTransition($transition) {}
	
	// windowing events
	
	function onWindowClose($login, $window) {}
	
	// threading events
	
	function onThreadDies($thread) {}
	
	function onThreadRestart($thread) {}
	
	function onThreadStart($thread) {}
	
	function onThreadTimesOut($thread) {}
	
	// ticker event
	
	function onTick() {}
	
	// storage events
	
	function onPlayerNewBestScore($player, $score_old, $score_new) {}
	
	function onPlayerNewBestTime($player, $best_old, $best_new) {}
	
	function onPlayerNewRank($player, $rank_old, $rank_new) {}
}
?>