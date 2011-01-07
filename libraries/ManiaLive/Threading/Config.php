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
	public $busy_timeout;
	public $ping_timeout;
	public $sequential_timeout;
	public $chunk_size;
	
	function validate()
	{
		$this->setDefault('enabled', false);
		$this->setDefault('busy_timeout', 20);
		$this->setDefault('ping_timeout', 2);
		$this->setDefault('sequential_timeout', 1);
		$this->setDefault('chunk_size', 10);
	}
}

?>