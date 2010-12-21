<?php

namespace ManiaLive\Threading;

/**
 * Jobs need to extend this class
 * before you can add them to the
 * ThreadPool!
 * @author Florian Schnell
 */
abstract class Runnable
{
	/**
	 * This method will be run on another
	 * process.
	 */
	abstract function run();
}

?>