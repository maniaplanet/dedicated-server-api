<?php

namespace ManiaLive\Threading;

class Config extends \ManiaLive\Config\Configurable
{
	public $enabled;
	public $busy_timeout;
	public $ping_timeout;
	public $sequential_timeout;
	public $chunk_size;
	
	function validate()
	{
		$this->setDefault('enabled', false);
		$this->setDefault('busy_timeout', 20);
		$this->setDefault('ping_timeout', 2);
		$this->setDefault('sequential_timeout', 1);
		$this->setDefault('chunk_size', 10);
	}
}

?>