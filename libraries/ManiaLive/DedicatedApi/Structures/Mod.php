<?php
/**
 * Represents a Mod for a TrackMania Dedicated Server
 * @copyright NADEO (c) 2010
 */
namespace ManiaLive\DedicatedApi\Structures;

class Mod extends AbstractStructure
{
	public $env;
	public $url;
	
	function toArray()
	{
		return array('Env'=>$this->env,'Url'=>$this->url);
	}
}