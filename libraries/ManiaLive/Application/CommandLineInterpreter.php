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

namespace ManiaLive\Application;

abstract class CommandLineInterpreter
{
	static function preConfigLoad()
	{
		$options = getopt(null,array(
			'help::',//Display Help
			'manialive_cfg::',//Set a configuration file to load instead of config.ini
		));

		$help = 'ManiaLive '."\n"
		.'Authors : '."\n"
		.'	Philippe "farfa" Melot, Maxime "Gouxim" Raoust, Florian "aseco" Schnell, Gwendal "Newbo.O" Martin'.PHP_EOL
		.'Usage: php bootstrapper.php [args]'.PHP_EOL
		.'Arguments:'."\n"
		.'  --help               - displays the present help'.PHP_EOL
		.'  --rpcport=xxx        - xxx represents the xmlrpc to use for the connection to the server'.PHP_EOL
		.'  --address=xxx        - xxx represents the address of the server, it should be an IP address or localhost'.PHP_EOL
		.'  --user=xxx           - xxx represents the name of the user to use for the communication. It should be User, Admin or SuperAdmin'.PHP_EOL
		.'  --password=xxx       - xxx represents the password relative to --user Argument'.PHP_EOL
		.'  --dedicated_cfg=xxx  - xxx represents the name of the Dedicated configuration file to use to get the connection data. This file should be present in the Dedicated\'s config file.'.PHP_EOL
		.'  --logsPrefix=xxx     - xxx reprents the prefix used for this instance log.'.PHP_EOL
		.'  --debug              - activate debug log.'.PHP_EOL;

		if(isset($options['help']))
		{
			echo $help;
			exit;
		}

		if(isset($options['manialive_cfg']))
			return $options['manialive_cfg'];
		else
			return 'config.ini';
	}

	static function postConfigLoad()
	{
		$options = getopt(null,array(
			'rpcport::',//Set the XML RPC Port to use
			'address::',//Set the adresse of the server
			'password::',//Set the User Password
			'dedicated_cfg::',//Set the configuration file to use to define XML RPC Port, SuperAdmin, Admin and User passwords
			'user::',//Set the user to use during the communication with the server
			'logsPrefix::', //Set the log prefix option
			'debug::' // Set up the debug option
		));

		$serverConfig = \ManiaLive\DedicatedApi\Config::getInstance();

		if (isset($options['logsPrefix']))
		{
			\ManiaLive\Config\Config::getInstance()->logsPrefix = $options['logsPrefix'];
		}

		if (isset($options['debug']))
		{
			\ManiaLive\Config\Config::getInstance()->debug = true;
		}

		if(isset($options['user']))
		{
			if($options['user'] != 'SuperAdmin' && $options['user'] != 'Admin' && $options['user'] != 'User')
			{
				echo 'Invalid Username'.PHP_EOL.$help;
				exit;
			}

			$serverConfig->user = $options['user'];
		}

		if(isset($options['dedicated_cfg']))
		{
			$filename = \ManiaLive\Config\Config::getInstance()->dedicatedPath.'/UserData/Config/'.$options['dedicated_cfg'];
			if(file_exists($filename))
			{
				//Load the config file
				$config = simplexml_load_file($filename);
				$serverConfig->port = (int)$config->system_config->xmlrpc_port;
				foreach ($config->authorization_levels->children() as $level)
				{
					if($serverConfig->user == (string)$level->name)
					{
						$serverConfig->password = (string)$level->password;
						break;
					}
				}
			}
			else
				throw new Exception('Configuration file not found...'.PHP_EOL.'stopping software...');
		}
		else
		{
			if(isset($options['rpcport']))
				$serverConfig->port = $options['rpcport'];
			if(isset($options['password']))
				$serverConfig->password = $options['password'];
		}

		if(isset($options['address']))
			$serverConfig->host = $options['address'];
	}
}
