<?php
/**
 * Represents the system which hosts a Dedicated Server
 * @copyright NADEO (c) 2010
 */
namespace ManiaLive\DedicatedApi\Structures;

class SystemInfos extends AbstractStructure
{
	public $publishedIp;
	public $port;
	public $p2PPort;
	public $serverLogin;
	public $serverPlayerId;
}
?>