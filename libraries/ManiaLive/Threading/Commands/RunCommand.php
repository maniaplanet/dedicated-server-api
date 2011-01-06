<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\Threading\Commands;

use ManiaLive\Threading\Runnable;

use ManiaLive\Threading\WrongTypeException;

class RunCommand extends Command
{
	function __construct(Runnable $runnable, $callback = null)
	{
		parent::__construct('run', $callback);
		$this->param = $runnable;
	}
}


?>