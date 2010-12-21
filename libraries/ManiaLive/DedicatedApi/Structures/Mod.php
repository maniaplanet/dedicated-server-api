<?php
/**
 * 
 * Represents a Mod for a TrackMania Dedicated Server
 * @author Philippe Melot
 * @copyright NADEO (c) 2010
 * @package ManiaMod
 * @subpackage Structures
 *
 */
namespace ManiaLive\DedicatedApi\Structures;

/**
 * 
 * Represents a Mod for a TrackMania Dedicated Server
 * @author Philippe Melot
 * @copyright NADEO (c) 2010
 * 
 */
class Mod extends AbstractStructure
{
	public $env;
	public $url;
	
	function toArray()
	{
		return array('Env'=>$this->env,'Url'=>$this->url);
	}
}