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

/**
 * Description of ClientMove
 */
class ClientMove extends AbstractTask
{
	private $clientId;
	private $channelId;
	
	function __construct($config, $clientId, $channelId)
	{
		parent::__construct($config);
		$this->clientId = $clientId;
		$this->channelId = $channelId;
	}
	
	protected function doRun()
	{
		$this->connection()->clientMove($this->clientId, $this->channelId);
	}
}

?>