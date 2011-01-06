<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\Threading;

/**
 * @author Florian Schnell
 */
interface Listener extends \ManiaLive\Event\Listener
{
	function onThreadStart($thread);
	function onThreadRestart($thread);
	function onThreadDies($thread);
	function onThreadTimesOut($thread);
}

?>