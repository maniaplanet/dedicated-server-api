<?php
/**
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */
namespace ManiaLivePlugins\Standard\AutoTagMatchSettings;

class Plugin extends \ManiaLive\PluginHandler\Plugin
{
	public function onLoad()
	{
		$settings = $this->connection->getModeScriptSettings();
		foreach ($settings as $setting => $value)
		{
			$this->connection->setServerTag('MS-'.$setting, $value, true);
		}
		$this->connection->executeMulticall();
	}
}
?>