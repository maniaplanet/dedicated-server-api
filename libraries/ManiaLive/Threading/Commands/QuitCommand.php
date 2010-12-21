<?php

namespace ManiaLive\Threading\Commands;

class QuitCommand extends Command
{
	function __construct($callback = null)
	{
		parent::__construct('exit', $callback);
	}
}

?>