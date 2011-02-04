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

/**
 * @author Florian Schnell
 */
interface Listener extends \ManiaLive\Event\Listener
{
	/**
	 * Event launch when a Plugin is loaded
	 * @param string $pluginId
	 */
	function onPluginLoaded($pluginId);
	/**
	 * Event launch when a plugin is unloaded
	 * @param string $classname
	 */
	function onPluginUnloaded($classname);
	}

?>