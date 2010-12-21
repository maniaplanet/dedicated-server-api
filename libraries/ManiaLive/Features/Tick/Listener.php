<?php

namespace ManiaLive\Features\Tick;

interface Listener extends \ManiaLive\Event\Listener
{
	function onTick();
}

?>