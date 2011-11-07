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
use ManiaLive\Utilities\Console;

/**
 * Load the plugins.
 * Manages dependencies and provides an interface to Plugins to communicate between each other.
 *
 * @author Florian Schnell
 */
class PluginHandler extends \ManiaLib\Utils\Singleton implements AppListener
{
	/**
	 * @var array[Plugins]
	 */
	protected $plugins;

	static function getClassFromPluginId($pluginId)
	{
		$parts = explode('\\', $pluginId);
		return '\\ManiaLivePlugins\\'.$pluginId.'\\'.end($parts);
	}

	static function getPluginIdFromClass($class)
	{
		return implode('\\', array_slice(explode('\\', $class), -3, 2));
	}

	final function __construct()
	{
		$this->plugins = array();
		Dispatcher::register(AppEvent::getClass(), $this, AppEvent::ON_INIT | AppEvent::ON_TERMINATE);
	}

	final function addPlugin($classname)
	{
		$plugin = $this->loadPlugin($classname);

		try
		{
			$this->checkPluginDependency($plugin);
			$plugin->onLoad();
			Dispatcher::dispatch(new Event(Event::ON_PLUGIN_LOADED, $plugin->getId()));
		}
		catch(\Exception $e)
		{
			$this->unloadPlugin($classname);
			ErrorHandling::processRuntimeException($e);
			return false;
		}

		$plugin->onReady();
		return true;
	}
	
	final function deletePlugin($className)
	{
		$this->unloadPlugin($className);
	}

	/**
	 * Retrieves a Plugin from the intern maintained list.
	 * @param string $name
	 * @return \ManiaLive\PluginHandler\Plugin
	 */
	final private function getPlugin($pluginId)
	{
		return (isset($this->plugins[$pluginId]) ? $this->plugins[$pluginId] : null);
	}

	/**
	 * Tries to register the Plugin in the intern list.
	 * Success depends on whether a Plugin with the current name has been registered yet.
	 * @param \ManiaLive\Application\Plugin $plugin
	 * @return bool
	 */
	final private function registerPlugin(Plugin $plugin)
	{
		$id = $plugin->getId();
		if(!isset($this->plugins[$id]))
		{
			$this->plugins[$id] = $plugin;
			return true;
		}

		return false;
	}

	/**
	 * Process of checking all plugin dependencies
	 * and initializing the plugins.
	 * @throws Exception
	 */
	final private function loadPlugins()
	{
		Console::println('[PluginHandler] Start plugin load process:');

		foreach(\ManiaLive\Application\Config::getInstance()->plugins as $pluginId)
		{
			$className = self::getClassFromPluginId($pluginId);

			if(!class_exists($className))
				$className = '\\ManiaLivePlugins\\'.$pluginId.'\\Plugin';

			$this->loadPlugin($className);
		}

		foreach($this->plugins as $id => $plugin)
		{
			try
			{
				$this->checkPluginDependency($plugin);
				$plugin->onLoad();
				Dispatcher::dispatch(new Event(Event::ON_PLUGIN_LOADED, $id));
			}
			catch(\Exception $e)
			{
				$this->unloadPlugin(self::getClassFromPluginId($id));
				ErrorHandling::processRuntimeException($e);
			}
		}

		foreach($this->plugins as $plugin)
			$plugin->onReady();

		Console::println('[PluginHandler] All registered plugins have been loaded');
	}

	final private function loadPlugin($className)
	{
		// check whether plugin could be loaded ...
		if(class_exists($className))
		{
			$plugin = new $className();
			
			Console::println('[PluginHandler] is loading '.$plugin->getId().' ...');
			$plugin->onInit();

			if(!$this->registerPlugin($plugin))
				throw new Exception("The plugin '{$plugin->getId()}' could not be registered, maybe there is a naming conflict!");

			return $plugin;
		}
		else
		{
			throw new Exception("Could not load Plugin '$className' !");
		}
	}

	final private function unloadPlugin($className)
	{
		$pluginId = self::getPluginIdFromClass($className);
		if(isset($this->plugins[$pluginId]))
		{
			foreach($this->plugins as $plugin)
			{
				foreach($plugin->getDependencies() as $dependency)
				{
					if($dependency->getPluginId() == $pluginId)
						throw new Exception('The plugin '.$className.' cannot be unloaded. It still has dependencies');
				}
			}
			$this->plugins[$pluginId]->onUnload();
			unset($this->plugins[$pluginId]);
			
			Dispatcher::dispatch(new Event(Event::ON_PLUGIN_UNLOADED, $pluginId));
		}
	}

	/**
	 * Checks the dependencies for one plugin.
	 * @param Plugin $plugin The Plugin to check for dependencies.
	 * @throws DependencyTooOldException
	 * @throws DependencyTooNewException
	 * @throws DependencyNotFoundException
	 */
	final private function checkPluginDependency(Plugin $plugin)
	{
		foreach($plugin->getDependencies() as $dependency)
		{
			// look whether dependent plugin exists at all
			$depPluginName = $dependency->getPluginId();
			if(isset($this->plugins[$depPluginName]))
			{
				$dependentPlugin = $this->plugins[$depPluginName];

				if($dependency->getMinVersion() != Dependency::NO_LIMIT && version_compare($dependentPlugin->getVersion(), $dependency->getMinVersion()) < 0)
					throw new DependencyTooOldException($plugin, $dependency);
				if($dependency->getMaxVersion() != Dependency::NO_LIMIT && version_compare($dependentPlugin->getVersion(), $dependency->getMaxVersion()) > 0)
					throw new DependencyTooNewException($plugin, $dependency);
			}

			// special case, check for core.
			elseif($depPluginName == 'ManiaLive')
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
	 * @param int $pluginId Id of the Plugin.
	 */
	final public function getPublicMethods($pluginId)
	{
		$plugin = $this->getPlugin($pluginId);

		return ($plugin == null ? null : $plugin->getPublicMethods());
	}

	/**
	 * Call a public method of a registered Plugin.
	 * @param Plugin $pluginCalling
	 * @param int $pluginId
	 * @param string $pluginMethod
	 * @param array $methodArgs
	 * @throws Exception
	 */
	final public function callPublicMethod(Plugin $pluginCalling, $pluginId, $pluginMethod, $methodArgs)
	{
		$plugin = $this->getPlugin($pluginId);

		if($plugin == null)
			throw new Exception("The plugin '$pluginId' which you want to call a method from, does not exist!");

		// add calling plugin as first parameter ...
		array_push($methodArgs, $pluginCalling->getId());

		// try to get the method we want to call from the owner-plugin
		$method = $plugin->getPublicMethod($pluginMethod);

		// invoke it ...
		return $method->invokeArgs($plugin, $methodArgs);
	}

	/**
	 * Checks whether a specific Plugin has been loaded.
	 * @param int $pluginId
	 * @return bool Whether the Plugin is loaded or not.
	 */
	final public function isPluginLoaded($pluginId, $min = Dependency::NO_LIMIT, $max = Dependency::NO_LIMIT)
	{
		return isset($this->plugins[$pluginId])
			&& ($min == Dependency::NO_LIMIT || version_compare($this->plugins[$pluginId]->getVersion(), $min) >= 0)
			&& ($max == Dependency::NO_LIMIT || version_compare($this->plugins[$pluginId]->getVersion(), $max) <= 0);
	}

	/**
	 * @return array[\ManiaLive\PLuginHandler\Plugin]
	 */
	final public function getLoadedPluginsList()
	{
		return array_keys($this->plugins);
	}

	function onInit()
	{
		$this->loadPlugins();
	}

	function onTerminate()
	{
		unset($this->plugins);
	}

	function onRun() {}
	function onPreLoop() {}
	function onPostLoop() {}
}

class Exception extends \Exception {}
