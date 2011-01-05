<?php

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
 * @copyright 2010 NADEO
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
	final function __construct($config_file = null)
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
			$class_name = '\\ManiaLivePlugins\\' . $path . '\\' . end($items);
			spl_autoload_call($class_name);
			
			// check whether plugin could be loaded ...
			if (class_exists($class_name))
			{
				$plugin = new $class_name($path);
				
				// init plugin ...
				$plugin->onInit();
				
				// register plugin ...
				if (!$this->registerPlugin($plugin))
					throw new Exception("The plugin '{$plugin->getId()}' could not be registered, maybe there is a naming conflict!");
			}
			else
			{
				throw new Exception("Could not load Plugin '$class_name' !");
			}
		}
		
		// load config settings ...
		foreach ($this->plugins as $plugin)
		{
			$class_name = get_class($plugin);
			$class = new \ReflectionClass($class_name);
			$properties = $class->getProperties();
			$plugin_id = $plugin->getId();
			$available = array();
			
			// foreach public static property ...
			foreach ($properties as $property)
			{
				if (!$property->isStatic() || !$property->isPublic()) continue;
				
				$property_name = $property->getName();
				$settings = Loader::$config->plugins->$plugin_id;
				$available[$property_name] = true;
				
				// if it is overwritten by the config
				if (isset($settings[$property_name]))
				{
					Console::printDebug("Overwriting config property '$plugin_id.$property_name' with value '".print_r($settings[$property_name],true)."'");
					$class_name::$$property_name = $settings[$property_name];
				}
			}
			
			// report every config setting that could not be used!
			foreach (Loader::$config->plugins->$plugin_id as $key => $value)
				if (!isset($available[$key]))
					Console::println("[Attention] '$plugin_id.$key' is not a valid setting!");
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
			$plugin->onReady();
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
		$dep_plugin_name = null;
		$dependent_plugin = null;
		
		foreach ($dependencies as $dependency)
		{
			// look whether dependent plugin exists at all
			$dep_plugin_name = $dependency->getPluginId();
			if (isset($this->plugins[$dep_plugin_name]))
			{
				$dependent_plugin = $this->plugins[$dep_plugin_name];
				
				// check min version number ...
				if ($dependent_plugin->getVersion() < $dependency->getMinVersion()
					&& $dependency->getMinVersion() != Dependency::NO_LIMIT)
					throw new DependencyTooOldException($plugin, $dependency);
					
				// check max version number ...
				if ($dependent_plugin->getVersion() > $dependency->getMaxVersion()
					&& $dependency->getMaxVersion() != Dependency::NO_LIMIT)
					throw new DependencyTooNewException($plugin, $dependency);
			}
			
			// special case, check for core.
			elseif ($dep_plugin_name == 'ManiaLive')
			{
				if (\ManiaLiveApplication\Version < $dependency->getMinVersion()
					&& $dependency->getMinVersion() != Dependency::NO_LIMIT)
					throw new DependencyTooOldException($plugin, $dependency);
					
				if (\ManiaLiveApplication\Version > $dependency->getMaxVersion()
					&& $dependency->getMaxVersion() != Dependency::NO_LIMIT)
					throw new DependencyTooNewException($plugin, $dependency);
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
	 * @param string $plugin_name Name of the Plugin.
	 */
	final public function getPublicMethods($plugin_id)
	{
		$plugin = $this->getPlugin($plugin_id);
		
		if ($plugin == null)
		{
			return $plugin->getPublicMethods();
		}
		else
		{
			return null;
		}
	}
	
	/**
	 * Call a public method of a registered Plugin.
	 * @param Plugin $plugin_calling
	 * @param string $plugin_name
	 * @param string $plugin_method
	 * @param array $method_args
	 * @throws Exception
	 */
	final public function callPublicMethod(Plugin $plugin_calling, $plugin_id, $plugin_method, $method_args)
	{
		$plugin = $this->getPlugin($plugin_id);
		
		if ($plugin == null)
		{
			throw new Exception("The plugin '$plugin_id' which you want to call a method from, does not exist!");
		}
		
		// add calling plugin as first parameter ...
		array_unshift($method_args, $plugin_calling->getId());
		
		// try to get the method we want to call from the owner-plugin
		$method = $plugin->getPublicMethod($plugin_method);
		
		// invoke it ...
		return $method->invokeArgs($plugin, $method_args);
	}
	
	/**
	 * Retrieves a Plugin from the intern maintained list.
	 * @param string $name
	 * @return ManiaLive\PluginHandler\Plugin
	 */
	final protected function getPlugin($plugin_id)
	{
		if (isset($this->plugins[$plugin_id]))
			return $this->plugins[$plugin_id];
		else
			return null;
	}
	
	/**
	 * Checks whether a specific Plugin has been loaded.
	 * @param string $name
	 * @return bool Whether the Plugin is loaded or not.
	 */
	final public function isPluginLoaded($plugin_id, $min = Dependency::NO_LIMIT, $max = Dependency::NO_LIMIT)
	{
		return (isset($this->plugins[$plugin_id])
			&& ($this->plugins[$plugin_id]->getVersion() >= $min || $min == Dependency::NO_LIMIT)
			&& ($this->plugins[$plugin_id]->getVersion() <= $max || $max == Dependency::NO_LIMIT));
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