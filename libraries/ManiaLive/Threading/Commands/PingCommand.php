<?php

namespace ManiaLive\Threading\Commands;

class PingCommand extends Command
{
	function __construct($callback = null)
	{
		parent::__construct('ping', $callback);
	}
}

?>