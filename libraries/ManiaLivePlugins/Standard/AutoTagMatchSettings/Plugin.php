<?php
/**
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */
namespace ManiaLivePlugins\Standard\AutoTagMatchSettings;

use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;

class Plugin extends \ManiaLive\PluginHandler\Plugin
{
	public function onInit()
	{
		$this->setPublicMethod('setModeScriptSettingsTags');
	}
	
	public function onLoad()
	{
		$this->setModeScriptSettingsTags();

		$this->enableDedicatedEvents(
			ServerEvent::ON_BEGIN_MAP
		);
	}

	public function onBeginMap($map, $warmUp, $matchContinuation)
	{
		$this->setModeScriptSettingsTags();
	}

	public function setModeScriptSettingsTags()
	{
		$settings = $this->connection->getModeScriptSettings();
		foreach ($settings as $setting => $value)
		{
			$this->connection->setServerTag($setting, json_encode($value), true);
		}
		$this->connection->executeMulticall();
	}
}
?>