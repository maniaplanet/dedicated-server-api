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
 * Jobs need to extend this class
 * before you can add them to the
 * ThreadPool!
 * 
 * @author Florian Schnell
 */
interface Runnable
{
	/**
	 * This method will be run on another process.
	 */
	function run();
}

?>