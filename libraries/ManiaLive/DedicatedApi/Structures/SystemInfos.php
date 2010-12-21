<?php
/**
 *
 * Represents the system which hosts a Dedicated Server
 * @author Philippe Melot
 * @copyright NADEO (c) 2010
 * @package ManiaMod
 * @subpackage Structures
 *
 */
namespace ManiaLive\DedicatedApi\Structures;

/**
 *
 * Represents the system which hosts a Dedicated Server
 * @author Philippe Melot
 * @copyright NADEO (c) 2010
 *
 */
class SystemInfos extends AbstractStructure
{
	public $publishedIp;
	public $port;
	public $p2PPort;
	public $serverLogin;
	public $serverPlayerId;
}
?>