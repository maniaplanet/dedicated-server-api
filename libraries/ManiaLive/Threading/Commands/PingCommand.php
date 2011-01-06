<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\Threading\Commands;

class PingCommand extends Command
{
	function __construct($callback = null)
	{
		parent::__construct('ping', $callback);
	}
}

?>