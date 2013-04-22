<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace DedicatedApi\Structures;

class Team extends AbstractStructure
{
	public $name;
	public $zonePath;
	public $city;
	public $emblemUrl;
	public $huePrimary;
	public $hueSecondary;
	public $rGB;
	public $clubLinkUrl;
}

?>