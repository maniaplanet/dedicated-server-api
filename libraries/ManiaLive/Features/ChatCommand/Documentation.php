<?php

namespace ManiaLive\Features\ChatCommand;

use ManiaLive\Utilities\Singleton;
use ManiaLive\PluginHandler\Plugin;
use ManiaLive\PluginHandler\Listener;
use ManiaLive\Event\Dispatcher;

class Documentation extends Singleton implements Listener
{
	protected $plugins_commands;
	protected $current_commands;
	
	/**
	 * @return \ManiaLive\Features\ChatCommand\Documentation
	 */
	static function getInstance()
	{
		return parent::getInstance();
	}
	
	protected function __construct()
	{
		$this->plugins_commands = array();
		$this->current_commands = array();
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
		
		foreach ($this->plugins_commands as $module => $commands)
		{
			if (empty($commands)) continue;
			
			$buffer[] = '<h2 style="text-decoration:underline">' . $module . '</h2>';
			$buffer[] = '<div style="margin-left:20px;margin-bottom:10px;">';
			
			foreach ($commands as $name => $list)
			{
				foreach ($list as $args => $command)
				{
					$modifier = null;
					if ($command->isPublic)
						$modifier = '<i><font color="#000099">public</font></i> ';
					else
						$modifier = '<i><font color="#990000">hidden</font></i> ';
					
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
	
	function registerCommandsFor($module_name)
	{
		$commands = Interpreter::getInstance()->getRegisteredCommands();
		$plugin_commands = array_diff_assoc($commands, $this->current_commands);
		$this->current_commands = $commands;
		$this->plugins_commands[$module_name] = $plugin_commands;
	}
	
	function onPluginLoaded($plugin_id)
	{
		$this->registerCommandsFor($plugin_id);
	}
}

?>