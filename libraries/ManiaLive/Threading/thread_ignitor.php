<?php
/**
 * @copyright NADEO (c) 2010
 * @author Florian Schnell
 */

namespace ManiaLive\Threading;

/**
 * This is what is run on each new process
 * that is being created by the ThreadPool.
 */

// include the __autoload function
require_once __DIR__ . '/../../../utils.inc.php';

// create new process with command line id
$pid = intval($argv[1]);

// process id from the main program
$parent = intval($argv[2]);

// create process object
$p = new Process($pid, $parent);

// process life-loop ...
while (true)
{
	// pull for work ...
	if (!$p->getWork())
	{
		// ... every second!
		sleep(1);
		
		// on linux we need to check whether the main application is still
		// running, otherwise we will quit.
		if (APP_OS == 'UNIX' && !isParentRunning($parent)) die();
	}
}

/**
 * Linux only.
 * Check whether the parent process is still alive.
 * @param $command
 */
function isParentRunning($pid)
{
	// create our system command
	$cmd = "ps $pid";
 
	// run the system command and assign output to a variable
	exec($cmd, $output, $result);
 
	// check the number of lines that were returned
	if(count($output) >= 2)
	{
		// the process is still alive
		return strpos($output[1], 'bootstrapper.php') !== false;
	}
 
	// the process is dead
	return false;
}    
?>