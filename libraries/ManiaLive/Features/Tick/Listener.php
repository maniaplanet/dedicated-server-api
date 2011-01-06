<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\Features\Tick;

interface Listener extends \ManiaLive\Event\Listener
{
	function onTick();
}

?>