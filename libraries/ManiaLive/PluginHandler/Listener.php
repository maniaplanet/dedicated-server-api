<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\PluginHandler;

/**
 * @author Florian Schnell
 */
interface Listener extends \ManiaLive\Event\Listener
{
	function onPluginLoaded($plugin_id);
}

?>