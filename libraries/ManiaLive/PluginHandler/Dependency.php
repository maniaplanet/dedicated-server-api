<?php
namespace ManiaLive\PluginHandler;

/**
 * Explains a Plugin's dependency.
 * For instnace if a Plugin uses functionality of another Plugin
 * you can tell the PluginHandler that it needs it to be loaded as well in order
 * to run properly. Is the dependency not loaded an Excpetion is thrown which causes
 * the handler to stop loading of the Plugin.
 * 
 * @author Florian Schnell
 * @copyright 2010 NADEO
 */
final class Dependency
{
	private $min;
	private $max;
	private $plugin_id;
	const NO_LIMIT = null;

	/**
	 * @param $plugin_name The name of the Plugin that is needed.
	 * @param $min The version which the Plugin at least has to have.
	 * @param $max Maximum version that is known to be supported.
	 */
	function __construct($plugin_id, $min = self::NO_LIMIT, $max = self::NO_LIMIT)
	{
		// check parameters ...
		if (!is_numeric($min) && $min != self::NO_LIMIT)
			throw new InvalidArgumentException('Dependency minimum needs to be an integer!');
		if (!is_numeric($max) && $max != self::NO_LIMIT)
			throw new InvalidArgumentException('Dependency maximum needs to be an integer!');
		
		$this->plugin_id = $plugin_id;
		$this->min = $min;
		$this->max = $max;
	}
	
	public function getMaxVersion()
	{
		return $this->max;
	}
	
	public function getMinVersion()
	{
		return $this->min;
	}
	
	public function getPluginId()
	{
		return $this->plugin_id;
	}
}

class DependencyNotFoundException extends \Exception
{
	/**
	 * @param Plugin $plugin
	 * @param Dependency $dependency
	 */
	function __construct($plugin, $dependency)
	{
		// build message ...
		$message = 'Plugin "' . $plugin->getId() . '" needs the plugin "' . $dependency->getPluginId();
		$message .= '" to be installed!';
		
		parent::__construct($message);
	}
}

class DependencyTooOldException extends \Exception
{
	/**
	 * @param Plugin $plugin
	 * @param Dependency $dependency
	 */
	function __construct($plugin, $dependency)
	{
		// build message ...
		$message = 'Plugin "' . $plugin->getId() . '" needs "' . $dependency->getPluginId();
		$message .= '" to be at least version ' . $dependency->getMinVersion() . '!';
		
		parent::__construct($message);
	}
}

class DependencyTooNewException extends \Exception
{
	/**
	 * @param Plugin $plugin
	 * @param Dependency $dependency
	 */
	function __construct($plugin, $dependency)
	{
		// build message ...
		$message = 'Plugin "' . $plugin->getId() . '" needs an older version of "' . $dependency->getPluginId();
		$message .= '" to be installed, ' . $dependency->getMaxVersion() . ' at highest!';
		
		parent::__construct($message);
	}
}
?>