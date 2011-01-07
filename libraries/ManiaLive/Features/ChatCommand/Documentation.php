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

namespace ManiaLive\Features\ChatCommand;

use ManiaLive\Utilities\Singleton;
use ManiaLive\PluginHandler\Plugin;
use ManiaLive\PluginHandler\Listener;
use ManiaLive\Event\Dispatcher;

class Documentation extends Singleton implements Listener
{
	protected $pluginsCommands;
	protected $currentCommands;
	
	/**
	 * @return \ManiaLive\Features\ChatCommand\Documentation
	 */
	static function getInstance()
	{
		return parent::getInstance();
	}
	
	protected function __construct()
	{
		$this->pluginsCommands = array();
		$this->currentCommands = array();
		Dispatcher::register(\ManiaLive\PluginHandler\Event::getClass(), $this);
	}
	
	function create($file_name)
	{
		$commands = Interpreter::getInstance()->getRegisteredCommands();
		$file = APP_ROOT . '/' . $file_name;
		
		$fhandle = fopen($file, 'w+');
		
		$buffer = array();
		
		$buffer[] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">';
		$buffer[] = '<html>';
		$buffer[] = '<head>';
		$buffer[] = '<title>ManiaLive Chat Command Documentation</title>';
		$buffer[] = '</head>';
		$buffer[] = '<body text="#000000" bgcolor="#FFFFFF" link="#FF0000" alink="#FF0000" vlink="#FF0000">';
		$buffer[] = '<h1>ManiaLive Chat Command Documentation</h1>';
		$buffer[] = '<div style="margin-left:20px;">';
		
		foreach ($this->pluginsCommands as $module => $commands)
		{
			if (empty($commands))
			{
				continue;
			}
			
			$buffer[] = '<h2 style="text-decoration:underline">' . $module . '</h2>';
			$buffer[] = '<div style="margin-left:20px;margin-bottom:10px;">';
			
			foreach ($commands as $name => $list)
			{
				foreach ($list as $args => $command)
				{
					$modifier = null;
					if ($command->isPublic)
					{
						$modifier = '<i><font color="#000099">public</font></i> ';
					}
					else
					{
						$modifier = '<i><font color="#990000">hidden</font></i> ';
					}
					
					$buffer[] = '<div style="margin-bottom:10px;">';
					$buffer[] =  $modifier . '<b>' . $command->name . '</b> (' . $args . ') <br />';
					$buffer[] = '<i style="color:blue;">' . $command->help . '</i>';
					$buffer[] = '</div>';
				}
			}
			
			$buffer[] = '</div>';
		}
		
		$buffer[] = '</div>';
		$buffer[] = '</body>';
		$buffer[] = '</html>';
		
		fwrite($fhandle, implode(APP_NL, $buffer));
		fclose($fhandle);
	}
	
	function registerCommandsFor($moduleName)
	{
		$commands = Interpreter::getInstance()->getRegisteredCommands();
		$pluginCommands = array_diff_assoc($commands, $this->currentCommands);
		$this->currentCommands = $commands;
		$this->pluginsCommands[$moduleName] = $pluginCommands;
	}
	
	function onPluginLoaded($pluginId)
	{
		$this->registerCommandsFor($pluginId);
	}
}

?>