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

use ManiaLive\Application\ErrorHandling;
use ManiaLive\Cache\Cache;
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
class PluginHandler extends Singleton
implements \ManiaLive\Application\Listener
{
	/**
	 * @var array[Plugins]
	 */
	protected $plugins;
	protected $repositoryEntries;
	protected $repositoryLoaded;
	protected $settings;

	/**
	 * @return \ManiaLive\PluginHandler\PluginHandler
	 */
	static function getInstance()
	{
		return parent::getInstance();
	}

	static function getClassFromPluginId($pluginId)
	{
		$parts = explode('\\', $pluginId);
		$class = end($parts);
		return '\ManiaLivePlugins\\' . $pluginId . '\\' . $class;
	}

	static function getPluginIdFromClass($class)
	{
		$class = explode('\\', $class);
		array_shift($class);
		array_shift($class);
		array_pop($class);
		return implode('\\', $class);
	}

	/**
	 * @param string $config_file
	 */
	final function __construct()
	{
		$this->repositoryLoaded = false;
		$this->repositoryEntries = array();
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
		Console::println('[PluginHandler] Start plugin load process:');

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

			$this->loadPlugin($className);

		}

		// load config settings ...
		foreach ($this->plugins as $plugin)
		{
			$this->loadPluginConfiguration($plugin);
		}

		$plugins = array();

		foreach ($this->plugins as $id => $plugin)
		{
			if ($this->checkPluginDependency($plugin))
			{
				$plugins[] = array($id, $plugin->getVersion());
					
				// this plugin is accepted
				try
				{
					$plugin->onLoad();
				}
				catch (\Exception $e)
				{
					$this->unLoadPlugin(self::getClassFromPluginId($id));
					ErrorHandling::processRuntimeException($e);
					continue;
				}
					
				// plugin loaded!
				Dispatcher::dispatch(new Event($id, Event::ON_PLUGIN_LOADED));
			}
		}

		// everything's up and ready to go!
		foreach ($this->plugins as $plugin)
		{
			$plugin->onReady();
		}
		
		Console::println('[PluginHandler] All registered plugins have been loaded');
	}

	protected function loadPlugin($className)
	{
		// check whether plugin could be loaded ...
		if (class_exists($className))
		{
			$plugin = new $className(self::getPluginIdFromClass($className));

			Console::println('[PluginHandler] is loading ' . $plugin->getId() . ' ...');
				
			// init plugin ...
			$plugin->onInit();

			// register plugin ...
			if (!$this->registerPlugin($plugin))
			{
				throw new Exception("The plugin '{$plugin->getId()}' could not be registered, maybe there is a naming conflict!");
			}
				
			return $plugin;
		}
		else
		{
			throw new Exception("Could not load Plugin '$className' !");
		}
	}

	protected function loadPluginConfiguration(Plugin $plugin)
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

	protected function unLoadPlugin($className)
	{
		$pluginId = self::getPluginIdFromClass($className);
		if(array_key_exists($pluginId, $this->plugins))
		{
			foreach($this->plugins as $plugin)
			{
				foreach ($plugin->getDependencies() as $dependency)
				{
					if ($dependency->getPluginId() == $pluginId)
					throw new Exception('The plugin '.$className.' cannot be unloaded. It still has dependencies');
				}
			}
			$this->plugins[$pluginId]->onUnload();
				
			$this->removeRepositoryEntry($pluginId);
				
			$this->plugins[$pluginId] = null;
			unset($this->plugins[$pluginId]);
			Dispatcher::dispatch(new Event($pluginId, Event::ON_PLUGIN_UNLOADED));
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
	 * Returns the version of the currently loaded plugin code.
	 * @param string $pluginId
	 */
	final public function getPluginRepositoryUpdate($pluginId)
	{
		if (isset($this->plugins[$pluginId]))
		{
			$entry = $this->getRepositoryEntry($pluginId);
			if ($entry)
			{
				if ($this->plugins[$pluginId]->getRepositoryVersion() < $entry->version)
				{
					return $entry;
				}
			}
		}
		return null;
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
		array_push($methodArgs, $pluginCalling->getId());

		// try to get the method we want to call from the owner-plugin
		$method = $plugin->getPublicMethod($pluginMethod);

		// invoke it ...
		return $method->invokeArgs($plugin, $methodArgs);
	}

	final public function addPlugin($classname)
	{
		//Load Plugins
		$plugin = $this->loadPlugin($classname);
		$this->loadPluginConfiguration($plugin);

		if ($this->checkPluginDependency($plugin))
		{
			$this->refreshPluginRepositoryInfo($plugin->getId());
				
			// this plugin is accepted
			try
			{
				$plugin->onLoad();
			}
			catch (\Exception $e)
			{
				$this->unLoadPlugin($classname);
				ErrorHandling::processRuntimeException($e);
				return false;
			}
				
			// plugin loaded!
			Dispatcher::dispatch(new Event($plugin->getId(), Event::ON_PLUGIN_LOADED));
		}

		// everything's up and ready to go!
		$plugin->onReady();
		
		return true;
	}

	final public function deletePlugin($classname)
	{
		//Unload Plugins
		$this->unLoadPlugin($classname);

		// clean last parts from memory
		gc_collect_cycles();
	}

	/**
	 * Retrieves a Plugin from the intern maintained list.
	 * @param string $name
	 * @return \ManiaLive\PluginHandler\Plugin
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
			$list[$id] = $plugin->getId();
		}
		return $list;
	}

	final public function refreshPluginRepositoryInfo($pluginId)
	{
		if (!isset($this->plugins[$pluginId]))
		{
			return false;
		}

		$repositoryId = $this->plugins[$pluginId]->getRepositoryId();
		if ($repositoryId === null)
		{
			return false;
		}

		$client = new \ManiaLib\Rest\Client();
		$client->setAPIURL(APP_API);

		$response = null;
		try
		{
			$response = $client->execute('GET', '/manialive/repository/entry/' . $repositoryId . '/index.json');
		}
		catch (\Exception $ex)
		{
			if ($ex->getCode() == 404)
			{
				return false;
			}
		}

		if ($response && isset($response->id) && isset($this->repositoryEntries[$response->id]))
		{
			$repositoryEntry = $this->repositoryEntries[$response->id];
			$entry = RepositoryEntry::fromResponse($response);
			if ($entry->version > $repositoryEntry->version)
			{
				$repositoryEntry->version = $entry->version;
				$repositoryEntry->name = $entry->name;
				$repositoryEntry->urlDownload = $entry->urlDownload;
				$repositoryEntry->urlInfo = $entry->urlInfo;
				$repositoryEntry->dateCreated = $entry->dateCreated;
				$repositoryEntry->category = $entry->category;
				$repositoryEntry->description = $entry->description;
			}
			$repositoryEntry->plugins[$pluginId] = $this->plugins[$pluginId]->getRepositoryVersion();
		}
		else
		{
			$entry = RepositoryEntry::fromResponse($response);
			$this->repositoryEntries[$entry->id] = $entry;
			$entry->plugins[$pluginId] = $this->plugins[$pluginId]->getRepositoryVersion();
		}

		return true;
	}

	final public function fetchPluginCacheEntry($pluginId, $key)
	{
		if (isset($this->plugins[$pluginId]))
		{
			return Cache::fetchFromModuleCache($this->plugins[$pluginId], $key);
		}
	}
	
	final public function existsPluginCacheEntry($pluginId, $key)
	{
		if (isset($this->plugins[$pluginId]))
		{
			return Cache::existsInModuleCache($this->plugins[$pluginId], $key);
		}
	}

	final public function refreshRepositoryInfo()
	{
		// build local repository
		foreach ($this->plugins as $plugin)
		{
			$id = $plugin->getRepositoryId();
			$version = $plugin->getRepositoryVersion();
			$entry = new RepositoryEntry();
				
			if ($id !== null)
			{
				if (isset($this->repositoryEntries[$id]))
				{
					if ($this->repositoryEntries[$id]->version > $version)
					{
						$this->repositoryEntries[$id]->version = $version;
					}
					$this->repositoryEntries[$id]->plugins[$plugin->getId()] = $plugin->getRepositoryVersion();
				}
				else
				{
					$entry = new RepositoryEntry();
					$entry->id = $id;
					$entry->version = $version;
					$entry->plugins[$plugin->getId()] = $plugin->getRepositoryVersion();
					$this->repositoryEntries[$id] = $entry;
				}
			}
		}

		// check all plugins for updates ...
		$toCheck = array();
		foreach ($this->repositoryEntries as $entry)
		{
			$toCheck[] = $entry->id;
		}
		
		try
		{
			$client = new \ManiaLib\Rest\Client();
			$client->setAPIURL(APP_API);
			$response = $client->execute('POST', '/manialive/repository/entries/index.json', array($toCheck));
	
			// every plugin has a response
			foreach ($response as $entry)
			{
				if ($entry)
				{
					$repositoryEntry = $this->repositoryEntries[$entry->id];
					$repositoryEntry->author = $entry->author;
					$repositoryEntry->name = $entry->name;
					$repositoryEntry->description = $entry->description;
					$repositoryEntry->urlDownload = $entry->address;
					$repositoryEntry->urlInfo = $entry->addressMore;
					$repositoryEntry->version = floatval($entry->version);
					$repositoryEntry->dateCreated = $entry->dateCreated;
					$repositoryEntry->category = $entry->category;
				}
			}
		}
		catch (\Exception $e)
		{
			$this->repositoryEntries = array();
		}
		$this->repositoryLoaded = true;
	}

	final public function removeRepositoryEntry($pluginId)
	{
		foreach ($this->repositoryEntries as $i => $entry)
		{
			if (isset($entry->plugins[$pluginId]))
			{
				unset($this->repositoryEntries[$i]->plugins[$pluginId]);
				if (count($this->repositoryEntries[$i]->plugins) == 0)
				{
					unset($this->repositoryEntries[$i]);
				}
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if there's a new version available for a loaded plugin.
	 * @param string $pluginId
	 * @return \ManiaLive\PluginHandler\RepositoryEntry
	 */
	final public function getRepositoryEntry($pluginId)
	{
		foreach ($this->repositoryEntries as $entry)
		{
			if (isset($entry->plugins[$pluginId]))
			{
				return $entry;
			}
		}
		return null;
	}

	/**
	 *
	 * Enter description here ...
	 */
	final public function isRepositoryLoaded()
	{
		return $this->repositoryLoaded;
	}

	/**
	 * Gets the references from the plugin repository
	 * of all the loaded plugins that could be found.
	 **/
	final public function getRepositoryEntries()
	{
		return $this->repositoryEntries;
	}

	/**
	 * Gets references from the plugin repository of all
	 * the plugins that are loaded.
	 * Only gets those plugins from repository that are newer
	 * than the currently running versions.
	 */
	final public function getRepositoryUpdates()
	{
		$updates = array();
		foreach ($this->repositoryEntries as $entry)
		{
			foreach ($entry->plugins as $id => $version)
			{
				if ($version < $entry->version)
				{
					if (isset($updates[$entry->id]))
					{
						$updates[$entry->id]->plugins[$id] = $version;
					}
					else
					{
						$rEntry = new RepositoryEntry();
						$rEntry->id = $entry->id;
						$rEntry->author = $entry->author;
						$rEntry->category = $entry->category;
						$rEntry->dateCreated = $entry->dateCreated;
						$rEntry->description = $entry->description;
						$rEntry->urlDownload = $entry->urlDownload;
						$rEntry->urlInfo = $entry->urlInfo;
						$rEntry->version = $entry->version;
						$rEntry->name = $entry->name;
						$rEntry->plugins[$id] = $version;
						$updates[$entry->id] = $rEntry;
					}
				}
			}
		}
		return $updates;
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