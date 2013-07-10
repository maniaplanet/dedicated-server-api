<?php
/**
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */
namespace ManiaLivePlugins\Standard\AutoTagManiaLive;

class Plugin extends \ManiaLive\PluginHandler\Plugin
{
	public function onLoad()
	{
		$this->connection->setServerTag('nl.controller', 'ManiaLive', true);
		$this->connection->setServerTag('nl.controller.version', (string) \ManiaLiveApplication\Version, true);
		$this->connection->executeMulticall();
	}
}
?>