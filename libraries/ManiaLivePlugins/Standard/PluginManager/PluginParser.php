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

class PluginParser implements \ManiaLive\Threading\Runnable
{
	function run()
	{
		$availablePlugins = array();

		foreach($this->searchFolderForPlugin(APP_ROOT.'libraries'.DIRECTORY_SEPARATOR.'ManiaLivePlugins') as $plugin)
			if(($class = $this->validatePlugin($plugin)) !== false)
				$availablePlugins[] = $class;

		return $availablePlugins;
	}

	private function validatePlugin($plugin)
	{
		$path = array();
		$start = false;
		$matches = array();
		if(preg_match('/(ManiaLivePlugins.*)\.php/', $plugin, $matches))
		{
			$class = '\\'.str_replace('/', '\\', $matches[1]);
			if(class_exists($class) && is_subclass_of($class, '\ManiaLive\PluginHandler\Plugin'))
				return implode('\\', array_slice(explode('\\', $class), 2, 2));
			else
				return false;
		}
	}

	private function searchFolderForPlugin($folder)
	{
		$plugins = array();

		$path = explode(DIRECTORY_SEPARATOR, $folder);
		$parent = end($path);

		foreach(scandir($folder) as $file)
		{
			if ($file == '.' || $file == '..')
				continue;

			$filePath = $folder.DIRECTORY_SEPARATOR.$file;

			//If it's a directory digg deeper
			if(is_dir($filePath))
				$plugins = array_merge($plugins, $this->searchFolderForPlugin($filePath));
			else
			{
				$pathParts = pathinfo($filePath);
				//If the file got the name of the parent folder or is called Plugin it should be a Plugin
				if($pathParts['filename'] == $parent || $pathParts['filename'] == 'Plugin')
					$plugins[] = $filePath;
			}
		}

		return $plugins;
	}
}


?>