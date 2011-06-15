<?php
/**
 * Plugin Manager - Plugin mades to load or unload ManiaLive plugins
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */
namespace ManiaLivePlugins\Standard\PluginManager;

use ManiaLive\Utilities\Console;

class PluginParser extends \ManiaLive\Threading\Runnable
{
	function run()
	{
		$availablePlugins = array();
		
		$pluginsDir = APP_ROOT . 'libraries/ManiaLivePlugins/';
		$plugins = $this->searchFolderForPlugin($pluginsDir);

		foreach ($plugins as $plugin)
		{
			if (($class = $this->validatePlugin($plugin)) !== false)
			{
				$availablePlugins[] = $class;
			}
		}
		
		return $availablePlugins;
	}

	protected function validatePlugin($plugin)
	{
		$path = array();
		$start = false;
		foreach (explode('/', $plugin) as $part)
		{
			if ($part == 'ManiaLivePlugins')
			{
				$start = true;
			}
			if ($part && $start)
			{
				$path[] = $part;
			}
		}
		$class = '\\'.str_replace('.php', '', implode('\\', $path));

		if (class_exists($class))
		{
			return is_subclass_of($class, '\ManiaLive\PluginHandler\Plugin') ? $class : false;
		}
		else
		{
			return false;
		}
	}

	protected function searchFolderForPlugin($folder)
	{
		$plugins = array();

		$folder = str_replace('\\', '/', $folder);
		$path = explode('/', $folder);
		$parent = end($path);

		$handle = opendir($folder);
		while($file = readdir($handle))
		{
			if ($file == '.' || $file == '..')
			{
				continue;
			}

			$filePath = $folder . '/' . $file;

			//If it's a directory digg deeper
			if (is_dir($filePath))
			{
				$plugins = array_merge($plugins, $this->searchFolderForPlugin($filePath));
			}
			else
			{
				$pathParts = pathinfo($filePath);
				//If the file got the name of the parent folder or is called Plugin it should be a Plugin
				if ($pathParts['filename'] == $parent || $pathParts['filename'] == 'Plugin')
				{
					$plugins[] = $filePath;
				}
			}
		}
		closedir($handle);

		return $plugins;
	}
}


?>