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

namespace ManiaLive\Threading;

/**
 * @author Florian Schnell
 */
class Config extends \ManiaLive\Config\Configurable
{
	public $enabled;
	public $busyTimeout;
	public $pingTimeout;
	public $sequentialTimeout;
	public $chunkSize;
	
	function validate()
	{
		$this->setDefault('enabled', false);
		$this->setDefault('busyTimeout', 20);
		$this->setDefault('pingTimeout', 2);
		$this->setDefault('sequentialTimeout', 1);
		$this->setDefault('chunkSize', 10);
	}
}

?>