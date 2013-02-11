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

namespace ManiaLive\PluginHandler;

use ManiaLive\Event\Dispatcher;
use ManiaLive\Application\Listener as AppListener;
use ManiaLive\Application\Event as AppEvent;
use ManiaLive\Application\ErrorHandling;
use ManiaLive\Data\Storage;
use ManiaLive\DedicatedApi\Callback\Listener as ServerListener;
use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;
use DedicatedApi\Structures\Status;
use ManiaLive\Utilities\Console;

/**
 * Load the plugins.
 * Manages dependencies and provides an interface to Plugins to communicate between each other.
 */
final class PluginHandler extends \ManiaLib\Utils\Singleton implements AppListener, ServerListener
{
	private $loadedPlugins = array();
	private $delayedPlugins = array();

	protected function __construct()
	{
		Dispatcher::register(AppEvent::getClass(), $this, AppEvent::ON_INIT | AppEvent::ON_TERMINATE);
		Dispatcher::register(ServerEvent::getClass(), $this, ServerEvent::ON_SERVER_START | ServerEvent::ON_SERVER_STOP);
	}
	
	function load($pluginId)
	{
		try
		{
			if( !($plugin = $this->register($pluginId)) )
				return false;
			
			$this->prepare($plugin);
			$plugin->onReady();
			return true;
		}
		catch(\Exception $e)
		{
			$this->unload($pluginId);
			ErrorHandling::processRuntimeException($e);
			return false;
		}
	}

	/**
	 * Checks whether a specific Plugin has been loaded.
	 * @param int $pluginId
	 * @return bool Whether the Plugin is loaded or not.
	 */
	function isLoaded($pluginId, $min = Dependency::NO_LIMIT, $max = Dependency::NO_LIMIT)
	{
		return isset($this->loadedPlugins[$pluginId])
			&& ($min == Dependency::NO_LIMIT || version_compare($this->loadedPlugins[$pluginId]->getVersion(), $min) >= 0)
			&& ($max == Dependency::NO_LIMIT || version_compare($this->loadedPlugins[$pluginId]->getVersion(), $max) <= 0);
	}

	/**
	 * @return array[string]
	 */
	function getLoadedPluginsList()
	{
		return array_keys($this->loadedPlugins);
	}
	
	function unload($pluginId)
	{
		if(isset($this->loadedPlugins[$pluginId]))
		{
			foreach($this->loadedPlugins as $plugin)
				foreach($plugin->getDependencies() as $dependency)
					if($dependency->getPluginId() == $pluginId)
						throw new Exception('Plugin "'.$pluginId.'" cannot be unloaded. It is required by other plugins.');
			
			$this->loadedPlugins[$pluginId]->onUnload();
			unset($this->loadedPlugins[$pluginId]);
			
			Dispatcher::dispatch(new Event(Event::ON_PLUGIN_UNLOADED, $pluginId));
		}
		else if(isset($this->delayedPlugins[$pluginId]))
			unset($this->delayedPlugins[$pluginId]);
	}
	
	private function register($pluginId)
	{
		if(isset($this->loadedPlugins[$pluginId]) || isset($this->delayedPlugins[$pluginId]))
			throw new Exception('Plugin "'.$pluginId.'" cannot be loaded, maybe there is a naming conflict!');
		
		$parts = explode('\\', $pluginId);
		$className = '\\ManiaLivePlugins\\'.$pluginId.'\\'.end($parts);
		if(!class_exists($className))
		{
			$className = '\\ManiaLivePlugins\\'.$pluginId.'\\Plugin';
			if(!class_exists($className))
				throw new Exception('Plugin "'.$pluginId.'" not found!');
		}

		$plugin = new $className();
		$plugin->onInit();
		if(Storage::getInstance()->serverStatus->code > Status::LAUNCHING || $plugin instanceof WaitingCompliant)
		{
			Console::println('[PluginHandler] Loading plugin "'.$pluginId.'"...');
			return $this->loadedPlugins[$pluginId] = $plugin;
		}
		Console::println('[PluginHandler] Server is waiting, plugin "'.$pluginId.'" will be loaded later...');
		$this->delayedPlugins[$pluginId] = $plugin;
		return null;
	}
	
	private function prepare($plugin)
	{
		$this->checkDependencies($plugin);
		$plugin->onLoad();
		Dispatcher::dispatch(new Event(Event::ON_PLUGIN_LOADED, $plugin->getId()));
	}
	
	private function checkDependencies($plugin)
	{
		foreach($plugin->getDependencies() as $dependency)
		{
			// look whether dependent plugin exists at all
			$name = $dependency->getPluginId();
			if(isset($this->loadedPlugins[$name]))
			{
				$requiredPlugin = $this->loadedPlugins[$name];

				if($dependency->getMinVersion() != Dependency::NO_LIMIT && version_compare($requiredPlugin->getVersion(), $dependency->getMinVersion()) < 0)
					throw new DependencyTooOldException($plugin, $dependency);
				if($dependency->getMaxVersion() != Dependency::NO_LIMIT && version_compare($requiredPlugin->getVersion(), $dependency->getMaxVersion()) > 0)
					throw new DependencyTooNewException($plugin, $dependency);
			}

			// special case, check for core.
			else if($name == 'ManiaLive')
			{
				if($dependency->getMinVersion() != Dependency::NO_LIMIT && version_compare(\ManiaLiveApplication\Version, $dependency->getMinVersion()) < 0)
					throw new DependencyTooOldException($plugin, $dependency);
				if($dependency->getMaxVersion() != Dependency::NO_LIMIT && version_compare(\ManiaLiveApplication\Version, $dependency->getMaxVersion()) > 0)
					throw new DependencyTooNewException($plugin, $dependency);
			}

			// dependent plugin is not loaded!
			else
				throw new DependencyNotFoundException($plugin, $dependency);
		}
	}

	/**
	 * Get a list of the public methods for a registered Plugin.
	 * @param string $pluginId Id of the Plugin.
	 */
	function getPublicMethods($pluginId)
	{
		return isset($this->loadedPlugins[$pluginId]) ? $this->loadedPlugins[$pluginId]->getPublicMethods() : null;
	}

	/**
	 * Call a public method of a registered Plugin.
	 * @param Plugin $caller
	 * @param string $pluginId
	 * @param string $pluginMethod
	 * @param array $methodArgs
	 * @throws Exception
	 */
	function callPublicMethod(Plugin $caller, $pluginId, $pluginMethod, $methodArgs)
	{
		if(!isset($this->loadedPlugins[$pluginId]))
			throw new Exception('Plugin "'.$pluginId.'" which you want to call a method from, does not exist!');
		
		$plugin = $this->loadedPlugins[$pluginId];
		array_push($methodArgs, $caller->getId());
		$method = $plugin->getPublicMethod($pluginMethod);
		return $method->invokeArgs($plugin, $methodArgs);
	}

	function onInit()
	{
		Console::println('[PluginHandler] Start plugin load process:');

		foreach(\ManiaLive\Application\Config::getInstance()->plugins as $pluginId)
		{
			try
			{
				$this->register($pluginId);
			}
			catch(\Exception $e)
			{
				$this->unload($pluginId);
				ErrorHandling::processRuntimeException($e);
			}
		}
		
		foreach($this->loadedPlugins as $pluginId => $plugin)
		{
			try
			{
				$this->prepare($plugin);
			}
			catch(\Exception $e)
			{
				$this->unload($pluginId);
				ErrorHandling::processRuntimeException($e);
			}
		}
		
		foreach($this->loadedPlugins as $plugin)
			$plugin->onReady();

		Console::println('[PluginHandler] All registered plugins have been loaded');
	}

	function onRun() {}
	function onPreLoop() {}
	function onPostLoop() {}
	
	function onTerminate()
	{
		foreach($this->loadedPlugins as $pluginId => $plugin)
			$this->unload($pluginId);
	}
	
	function onServerStart()
	{
		foreach($this->delayedPlugins as $pluginId => $plugin)
		{
			try
			{
				$this->loadedPlugins[$pluginId] = $plugin;
				$this->prepare($plugin);
			}
			catch(\Exception $e)
			{
				$this->unload($pluginId);
				unset($this->delayedPlugins[$pluginId]);
				ErrorHandling::processRuntimeException($e);
			}
		}
		
		foreach($this->delayedPlugins as $plugin)
			$plugin->onReady();
		
		$this->delayedPlugins = array();
	}
	
	function onServerStop()
	{
		foreach($this->loadedPlugins as $pluginId => $plugin)
			if(!($plugin instanceof WaitingCompliant))
			{
				$this->unload($pluginId);
				$this->delayedPlugins[$pluginId] = $plugin;
			}
	}
	
	function onPlayerConnect($login, $isSpectator) {}
	function onPlayerDisconnect($login) {}
	function onPlayerChat($playerUid, $login, $text, $isRegistredCmd) {}
	function onPlayerManialinkPageAnswer($playerUid, $login, $answer, array $entries) {}
	function onEcho($internal, $public) {}
	function onBeginMatch() {}
	function onEndMatch($rankings, $winnerTeamOrMap) {}
	function onBeginMap($map, $warmUp, $matchContinuation) {}
	function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap) {}
	function onBeginRound() {}
	function onEndRound() {}
	function onStatusChanged($statusCode, $statusName) {}
	function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex) {}
	function onPlayerFinish($playerUid, $login, $timeOrScore) {}
	function onPlayerIncoherence($playerUid, $login) {}
	function onBillUpdated($billId, $state, $stateName, $transactionId) {}
	function onTunnelDataReceived($playerUid, $login, $data) {}
	function onMapListModified($curMapIndex, $nextMapIndex, $isListModified) {}
	function onPlayerInfoChanged($playerInfo) {}
	function onManualFlowControlTransition($transition) {}
	function onVoteUpdated($stateName, $login, $cmdName, $cmdParam) {}
	function onModeScriptCallback($param1, $param2) {}
	function onPlayerAlliesChanged($login) {}
}

class Exception extends \Exception {}
