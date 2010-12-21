<?php

namespace ManiaLive\Threading;

class Config extends \ManiaLive\Config\Configurable
{
	public $enabled;
	public $busy_timeout = 20;
	public $ping_timeout = 2;
	public $sequential_timeout = 1;
}

?>