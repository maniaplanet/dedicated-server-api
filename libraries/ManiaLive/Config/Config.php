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
	
	//Set to true to disable the updater
	public $lanMode;
	
	function validate()
	{
		$conf = array();
		if (file_exists(APP_ROOT . 'run.ini'))
		{
			$conf = parse_ini_file(APP_ROOT . 'run.ini');
			if ($conf === false)
			{
				$conf = array();
			}
		}
		
		if (!isset($conf['phpPath']))
		{
			$conf['phpPath'] = '';
		}
		
		// set this depending on os.
		if (APP_OS == 'WIN')
		{
			if (!$conf['phpPath'])
				$conf['phpPath'] = 'php.exe';
			$this->setDefault('phpPath', $conf['phpPath']);
		}
		else
		{
			if (!$conf['phpPath'])
				$conf['phpPath'] = '`which php`';
			$this->setDefault('phpPath', $conf['phpPath']);
		}
		
		$this->setDefault('logsPath', APP_ROOT . 'logs');
		
		$this->setDefault('admins', array());
		
		$this->setDefault('runtimeLog', false);
		
		$this->setDefault('globalErrorLog', false);
		
		$this->setDefault('maxErrorCount', false);
		
		$this->setDefault('logsPrefix', '');
		
		$this->setDefault('dedicatedPath', APP_ROOT);
		
		$this->setDefault('lanMode', false);
	}
}

?>