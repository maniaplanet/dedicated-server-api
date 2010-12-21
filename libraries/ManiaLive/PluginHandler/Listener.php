<?php

namespace ManiaLive\PluginHandler;

interface Listener extends \ManiaLive\Event\Listener
{
	function onPluginLoaded($plugin_id);
}

?>