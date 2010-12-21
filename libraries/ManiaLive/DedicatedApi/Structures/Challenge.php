<?php
/**
 *
 * Represents a Dedicated TrackMania Server Challenge
 * @author Philippe Melot
 * @copyright NADEO (c) 2010
 * @package ManiaMod
 * @subpackage Structures
 *
 */
namespace ManiaLive\DedicatedApi\Structures;

/**
 *
 * Represents a Dedicated TrackMania Server Challenge
 * @author Philippe Melot
 * @copyright NADEO (c) 2010
 * 
 */
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