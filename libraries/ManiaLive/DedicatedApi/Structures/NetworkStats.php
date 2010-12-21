<?php
/**
 *
 * Represents the Networks Statistics of a Dedicated TrackMania Server
 * @author Philippe Melot
 * @copyright NADEO (c) 2010
 * @package ManiaMod
 * @subpackage Structures
 *
 */
namespace ManiaLive\DedicatedApi\Structures;

/**
 *
 * Represents the Networks Statistics of a Dedicated TrackMania Server
 * @author Philippe Melot
 * @copyright NADEO (c) 2010
 *
 */
class NetworkStats extends AbstractStructure
{
	public $uptime;
	public $nbrConnection;
	public $meanConnectionTime;
	public $meanNbrPlayer;
	public $recvNetRate;
	public $sendNetRate;
	public $totalReceivingSize;
	public $totalSendingSize;
	public $playerNetInfos;

	static public function fromArray($array)
	{
		$object = parent::fromArray($array);
		$object->playerNetInfos = Player::fromArrayOfArray($object->playerNetInfos);
		return $object;
	}
}