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

namespace ManiaLive\Config;

class Config extends \ManiaLib\Utils\Singleton
{
	// base path for logging
	public $logsPath;
	public $logsPrefix = '';
	// enable runtime logging?
	public $runtimeLog = false;
	// log all errors from all instances?
	public $globalErrorLog = false;
	public $maxErrorCount = false;
	public $dedicatedPath = APP_ROOT;
	//Set to true to disable the updater
	public $lanMode = false;
	public $debug = false;

	function __construct()
	{
		$this->logsPath = APP_ROOT.'logs';
	}
}

?>
