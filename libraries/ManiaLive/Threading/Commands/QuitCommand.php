<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\Threading\Commands;

class QuitCommand extends Command
{
	function __construct($callback = null)
	{
		parent::__construct('exit', $callback);
	}
}

?>