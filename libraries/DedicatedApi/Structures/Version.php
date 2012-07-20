<?php
/**
 * Represents a Dedicated TrackMania Server Version
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */
namespace DedicatedApi\Structures;

class Version extends AbstractStructure
{
	public $name;
	public $titleId;
	public $version;
	public $build;
	public $apiVersion;
}
?>