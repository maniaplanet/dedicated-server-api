<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 1709 $:
 * @author      $Author: svn $:
 * @date        $Date: 2011-01-07 14:06:13 +0100 (ven., 07 janv. 2011) $:
 */

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
	
	public $dedicatedPath;
	
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
		
		$this->setDefault('dedicatedPath', APP_ROOT);
	}
}

?>