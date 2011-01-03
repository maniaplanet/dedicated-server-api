<?php

namespace ManiaLive\Config;

class Config extends Configurable
{
	/**
	 * @var \ManiaLive\DedicatedApi\Config
	 */
	public $server;
	
	/**
	 * @var \ManiaLive\Threading\Config
	 */
	public $threading;
	
	/**
	 * @var \ManiaHome\Config
	 */
	public $maniahome;
	
	/**
	 * @var \ManiaLive\PluginHandler\Config
	 */
	public $plugins;
	
	/**
	 * @var \ManiaLive\Features\Admin\Config
	 */
	public $admins;
	
	/**
	 * @var \ManiaLive\Features\ChatCommand\Config
	 */
	public $chatcommands;
	
	// depends on os
	public $phpPath;
	
	// base path for logging
	public $logsPath;
	
	public $logsPrefix;
	
	// enable runtime logging?
	public $runtimeLog;
	
	// log all errors from all instances?
	public $globalErrorLog;
	
	public $maxErrorCount;
	
	function validate()
	{
		// set this depending on os.
		if (APP_OS == 'WIN')
		{
			$this->setDefault('phpPath', 'php.exe');
		}
		else
		{
			$this->setDefault('phpPath', 'php');
		}
		
		$this->setDefault('logsPath', APP_ROOT . 'logs');
		
		$this->setDefault('admins', array());
		
		$this->setDefault('runtimeLog', false);
		
		$this->setDefault('globalErrorLog', false);
		
		$this->setDefault('maxErrorCount', false);
		
		$this->setDefault('logsPrefix', '');
	}
}

?>