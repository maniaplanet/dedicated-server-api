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
class Config extends \ManiaLib\Utils\Singleton
{
	public $phpPath;
	public $enabled = false;
	public $busyTimeout = 20;
	public $pingTimeout = 2;
	public $sequentialTimeout = 1;
	public $chunkSize = 10;

	function __construct()
	{
		if(APP_OS == 'WIN')
			$this->phpPath = 'php.exe';
		else
			$this->phpPath = '`which php`';
		
		$this->logsPath = APP_ROOT.'logs';
	}
}

?>