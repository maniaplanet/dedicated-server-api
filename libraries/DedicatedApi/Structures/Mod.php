<?php
/**
 * Represents a Mod for a TrackMania Dedicated Server
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */
namespace DedicatedApi\Structures;

class Mod extends AbstractStructure
{
	public $env;
	public $url;
	
	function toArray()
	{
		return array('Env'=>$this->env,'Url'=>$this->url);
	}
}