<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLive\Threading\Commands;

use ManiaLive\Threading\Runnable;

class RunCommand extends Command
{
	function __construct(Runnable $runnable, $callback = null)
	{
		parent::__construct('run', $callback);
		$this->param = $runnable;
	}
}


?>