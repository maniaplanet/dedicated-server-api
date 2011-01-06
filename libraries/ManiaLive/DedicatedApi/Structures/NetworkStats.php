<?php
/**
 * Represents the Networks Statistics of a Dedicated TrackMania Server
 * @copyright NADEO (c) 2010
 */
namespace ManiaLive\DedicatedApi\Structures;

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