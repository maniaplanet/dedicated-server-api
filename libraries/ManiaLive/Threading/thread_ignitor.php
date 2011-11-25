<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 * @author Florian Schnell
 */

namespace ManiaLive\Threading;

/**
 * This is what is run on each new process
 * that is being created by the ThreadPool.
 */
// include the __autoload function
require_once __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'autoload.php';

// create new process with command line id
$pid = intval($argv[1]);

// process id from the main program
$parent = intval($argv[2]);

// create process object
$p = new Process($pid, $parent);

// process life-loop ...
while(true)
{
    // pull for work ...
    if(!$p->getWork())
    {
	 // ... every second!
	 sleep(1);

	 // check whether the main application is still running, otherwise we will quit.
	 if(!isParentRunning($parent)) die();
    }
}

/**
 * Check whether the parent process is still alive.
 * @param $command
 */
function isParentRunning($pid)
{
    if(stripos(PHP_OS, 'WIN') !== 0)
    {
	 // run the system command and assign output to a variable
	 exec("ps $pid", $output, $result);

	 if(count($output) >= 2)
		  return strpos($output[1], 'bootstrapper.php') !== false;
	 return false;
    }
    else
    {
	 exec("tasklist /FI \"PID eq $pid\"", $output, $result);

	 if(count($output) >= 4) return strpos($output[3], 'php.exe') !== false;
	 return false;
    }
}

?>