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

use ManiaLive\Config\Loader;

use ManiaLive\DedicatedApi\ConnectionException;

use ManiaLive\Utilities\Console;

use ManiaLive\DedicatedApi\Connection;

abstract class CommandLineInterpreter
{
	static function preConfigLoad()
	{
		$options = getopt(null,array(
			'manialive_cfg::',//Display Help
		));
		
		if (array_key_exists('manialive_cfg', $options))
		{
			return $options['manialive_cfg'];
		}
		else
		{
			return 'config.ini';
		}
	}
	
	static function postConfigLoad()
	{
		$options = getopt(null,array(
			'help::',//Display Help
			'rpcport::',//Set the XML RPC Port to use
			'address::',//Set the adresse of the server
			'password::',//Set the User Password
			'cfg::',//Set the configuration file to use to define XML RPC Port, SuperAdmin, Admin and User passwords
			'user::'//Set the user to use during the communication with the server
		));

		$help = 'ManiaMod - TrackMania Dedicated Manager v0.3 (2010 Aug 16)'."\n"
		.'Authors : '."\n"
		.'	Philippe "farfa" Melot, Maxime "Gouxim" Raoust, Florian "aseco" Schnell'."\n"
		.'Usage: php index.php [args]'."\n"
		.'Arguments:'."\n"
		.'  --help               - displays the present help'."\n"
		.'  --rpcport=xxx        - xxx represents the xmlrpc to use for the connection to the server'."\n"
		.'  --address=xxx        - xxx represents the address of the server, it should be an IP address or localhost'."\n"
		.'  --user=xxx           - xxx represents the name of the user to use for the communication. It should be User, Admin or SuperAdmin'."\n"
		.'  --password=xxx       - xxx represents the password relative to --user Argument'."\n"
		.'  --dedicated_cfg=xxx  - xxx represents the name of the Dedicated configuration file to use to get the connection data. This file should be present in the Dedicated\'s config file.'."\n"
		.'  --manialive_cfg=xxx  - xxx represents the name of the ManiaLive\'s configuration file. This file should be present in the ManiaLive\'s config file.'."\n";

		if (array_key_exists('help', $options))
		{
			echo $help;
			exit;
		}
		
		$serverConfig = \ManiaLive\DedicatedApi\Config::getInstance();

		if (array_key_exists('user', $options))
		{
			if($options['user'] != 'SuperAdmin' && $options['user'] != 'Admin' && $options['user'] != 'User')
			{
				echo 'Invalid Username'.APP_NL.$help;
				exit;
			}

			$serverConfig->user = $options['user'];
		}

		if (array_key_exists('dedicated_cfg', $options))
		{
			$filename = __DIR__.'\\GameData\\Config\\'.$options['cfg'];
			if (file_exists($filename))
			{
				//Load the config file
				$dom = new \DOMDocument();
				$dom->load($filename);

				//Get the xml RPC port
				$nodeList = $dom->getElementsByTagName('xmlrpc_port');
				$serverConfig->port = (int)$nodeList->item(0)->nodeValue;

				$nodeList = $dom->getElementsByTagName('level');
				foreach ($nodeList as $node)
				{
					$name = $node->getElementsByTagName('name')->item(0)->nodeValue;
					$pass = $node->getElementsByTagName('password')->item(0)->nodeValue;
					if($serverConfig->user == $name)
					$serverConfig->password = $pass;
				}

				if (array_key_exists('address', $options))
				{
					$serverConfig->host = $options['address'];
				}
			}
			else
			{
				throw new Exception('configuration file not found.....'.APP_NL.'stopping software....');
			}
		}
		else
		{
			if (array_key_exists('rpcport', $options))
			{
				$serverConfig->port = $options['rpcport'];
			}
			if (array_key_exists('address', $options))
			{
				$serverConfig->host = $options['address'];
			}
			if (array_key_exists('password', $options))
			{
				$serverConfig->password = $options['password'];
			}
		}
	}
}
