<?php
/**
 * Represents a Dedicated TrackMania Server Challenge
 * @copyright NADEO (c) 2010
 */
namespace ManiaLive\DedicatedApi\Structures;

class Challenge extends AbstractStructure
{
	public $uId;
	public $name;
	public $fileName;
	public $author;
	public $environnement;
	public $mood;
	public $bronzeTime;
	public $silverTime;
	public $goldTime;
	public $authorTime;
	public $copperPrice;
	public $lapRace;
	public $nbLaps;
	public $nbCheckpoints;
}