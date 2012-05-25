<?php
/**
 * TeamSpeak Plugin - Connect to a TeamSpeak 3 server
 * Original work by refreshfr
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\Standard\TeamSpeak\Tasks;

use ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\TeamSpeak3;

/**
 * Description of AbstractTask
 */
abstract class AbstractTask implements \ManiaLive\Threading\Runnable
{
	static private $tsServer = null;
	private $config;
	
	function __construct($config)
	{
		$this->config = $config;
	}
	
	protected function connection()
	{
		if(!self::$tsServer)
			self::$tsServer = TeamSpeak3::factory('serverquery://'.$this->config->queryLogin.':'.$this->config->queryPassword.'@'.$this->config->queryHost.':'.$this->config->queryPort.'/?server_port='.$this->config->voicePort.'#no_query_clients');
		return self::$tsServer;
	}
	
	protected abstract function doRun();
	
	function run()
	{
		try
		{
			return $this->doRun();
		}
		catch(\Exception $e)
		{
			exit();
		}
	}
}

?>