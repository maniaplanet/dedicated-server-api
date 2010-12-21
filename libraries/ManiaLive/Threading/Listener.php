<?php

namespace ManiaLive\Threading;

interface Listener extends \ManiaLive\Event\Listener
{
	function onThreadStart($thread);
	function onThreadRestart($thread);
	function onThreadDies($thread);
	function onThreadTimesOut($thread);
}

?>