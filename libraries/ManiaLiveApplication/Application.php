<?php 

namespace ManiaLiveApplication;

if (extension_loaded('pcntl'))
	declare(ticks = 1); 

class Application extends \ManiaLive\Application\AbstractApplication
{
	function __construct()
	{
		if (extension_loaded('pcntl'))
		{
			pcntl_signal(SIGTERM, array($this, 'kill'));  
			pcntl_signal(SIGINT, array($this, 'kill'));
		}
		parent::__construct();
	}
}

?>