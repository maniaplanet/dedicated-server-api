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

use ManiaLive\Config\Loader;
use ManiaLive\Utilities\Singleton;
use ManiaLive\Utilities\Console;
use ManiaLive\PluginHandler\Plugin;
use ManiaLive\Utilities\XmlParser;
use ManiaLive\Event\Dispatcher;
use ManiaLive\Utilities;

/**
 * Scans the pluginhandler.xml for Plugins to load.
 * Manages dependencies and provides an interface to Plugins to communicate between
 * each other.
 *
 * @author Florian Schnell
 */
class PluginHandler extends Singleton implements \ManiaLive\Application\Listener
{
	/**
	 * @var array[Plugins]
	 */
	protected $plugins;
	protected $settings;

	/**
	 * @return \ManiaLive\PluginHandler\PluginHandler
	 */
	static function getInstance()
	{
		return parent::getInstance();
	}

	/**
	 * @param string $config_file
	 */
	final function __construct()
	{
		$this->plugins = array();
		Dispatcher::register(\ManiaLive\Application\Event::getClass(), $this);
	}

	/**
	 * Tries to register the Plugin in the intern list.
	 * Success depends on whether a Plugin with the current name has been registered yet.
	 * @param \ManiaLive\Application\Plugin $plugin
	 * @return bool
	 */
	final protected function registerPlugin(Plugin $plugin)
	{
		$name = null;

		// check for naming conflict
		$id = $plugin->getId();
		if (!isset($this->plugins[$id]))
		{
			// register plugin
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
	final protected function loadPlugins()
	{
		foreach (Loader::$config->plugins->load as $path)
		{
			$plugin = null;

			// load plugin class ...
			$items = explode('\\', $path);
			$className = '\\ManiaLivePlugins\\' . $path . '\\' . end($items);
			
			if (!class_exists($className))
			{
				$className = '\\ManiaLivePlugins\\'.$path.'\\Plugin';
			}

			// check whether plugin could be loaded ...
			if (class_exists($className))
			{
				$plugin = new $className($path);

				// init plugin ...
				$plugin->onInit();

				// register plugin ...
				if (!$this->registerPlugin($plugin))
				{
					throw new Exception("The plugin '{$plugin->getId()}' could not be registered, maybe there is a naming conflict!");
				}
			}
			else
			{
				throw new Exception("Could not load Plugin '$className' !");
			}
		}

		// load config settings ...
		foreach ($this->plugins as $plugin)
		{
			$className = get_class($plugin);
			$class = new \ReflectionClass($className);
			$properties = $class->getProperties();
			$pluginId = $plugin->getId();
			$available = array();

			// foreach public static property ...
			foreach ($properties as $property)
			{
				if (!$property->isStatic() || !$property->isPublic())
				{
					continue;
				}

				$propertyName = $property->getName();
				$settings = Loader::$config->plugins->$pluginId;
				$available[$propertyName] = true;

				// if it is overwritten by the config
				if (isset($settings[$propertyName]))
				{
					Console::printDebug("Overwriting config property '$pluginId.$propertyName' with value '".print_r($settings[$propertyName],true)."'");
					$className::$$propertyName = $settings[$propertyName];
				}
			}

			// report every config setting that could not be used!
			foreach (Loader::$config->plugins->$pluginId as $key => $value)
			{
				if (!isset($available[$key]))
				{
					Console::println("[Attention] '$pluginId.$key' is not a valid setting!");
				}
			}
		}

		foreach ($this->plugins as $plugin)
		{
			if ($this->checkPluginDependency($plugin))
			{
				// this plugin is accepted
				$plugin->onLoad();

				// plugin loaded!
				Dispatcher::dispatch(new Event($plugin->getId()));
			}
		}

		// everything's up and ready to go!
		foreach ($this->plugins as $plugin)
		{
			$plugin->onReady();
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
		$dependencies = $plugin->getDependencies();
		$depPluginName = null;
		$dependentPlugin = null;

		foreach ($dependencies as $dependency)
		{
			// look whether dependent plugin exists at all
			$depPluginName = $dependency->getPluginId();
			if (isset($this->plugins[$depPluginName]))
			{
				$dependentPlugin = $this->plugins[$depPluginName];

				// check min version number ...
				if ($dependentPlugin->getVersion() < $dependency->getMinVersion()
				&& $dependency->getMinVersion() != Dependency::NO_LIMIT)
				{
					throw new DependencyTooOldException($plugin, $dependency);
				}
					
				// check max version number ...
				if ($dependentPlugin->getVersion() > $dependency->getMaxVersion()
				&& $dependency->getMaxVersion() != Dependency::NO_LIMIT)
				{
					throw new DependencyTooNewException($plugin, $dependency);
				}
			}

			// special case, check for core.
			elseif ($depPluginName == 'ManiaLive')
			{
				if (\ManiaLiveApplication\Version < $dependency->getMinVersion()
				&& $dependency->getMinVersion() != Dependency::NO_LIMIT)
				{
					throw new DependencyTooOldException($plugin, $dependency);
				}
					
				if (\ManiaLiveApplication\Version > $dependency->getMaxVersion()
				&& $dependency->getMaxVersion() != Dependency::NO_LIMIT)
				{
					throw new DependencyTooNewException($plugin, $dependency);
				}
			}

			// dependent plugin is not loaded!
			else
			{
				// dependent plugin has not been found!
				throw new DependencyNotFoundException($plugin, $dependency);

				return false;
			}
		}
		return true;
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

		if ($plugin == null)
		{
			throw new Exception("The plugin '$pluginId' which you want to call a method from, does not exist!");
		}

		// add calling plugin as first parameter ...
		array_unshift($methodArgs, $pluginCalling->getId());

		// try to get the method we want to call from the owner-plugin
		$method = $plugin->getPublicMethod($pluginMethod);

		// invoke it ...
		return $method->invokeArgs($plugin, $methodArgs);
	}

	/**
	 * Retrieves a Plugin from the intern maintained list.
	 * @param string $name
	 * @return ManiaLive\PluginHandler\Plugin
	 */
	final protected function getPlugin($pluginId)
	{
		return (isset($this->plugins[$pluginId]) ? $this->plugins[$pluginId] : null);
	}

	/**
	 * Checks whether a specific Plugin has been loaded.
	 * @param int $pluginId
	 * @return bool Whether the Plugin is loaded or not.
	 */
	final public function isPluginLoaded($pluginId, $min = Dependency::NO_LIMIT, $max = Dependency::NO_LIMIT)
	{
		return (isset($this->plugins[$pluginId])
		&& ($this->plugins[$pluginId]->getVersion() >= $min || $min == Dependency::NO_LIMIT)
		&& ($this->plugins[$pluginId]->getVersion() <= $max || $max == Dependency::NO_LIMIT));
	}

	/**
	 * @return array[\ManiaLive\PLuginHandler\Plugin]
	 */
	final public function getLoadedPluginsList()
	{
		$list = array();
		foreach ($this->plugins as $id => $plugin)
		{
			$list[] = $id;
		}
		return $list;
	}

	// implement the listener interface ...

	function onInit()
	{
		$this->loadPlugins();
	}

	function onRun() {}

	function onPreLoop() {}

	function onPostLoop() {}

	function onTerminate()
	{
		unset($this->plugins);
	}
}

class Exception extends \Exception {}