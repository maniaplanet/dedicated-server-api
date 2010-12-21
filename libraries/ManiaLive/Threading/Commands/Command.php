<?php

namespace ManiaLive\Threading\Commands;

abstract class Command
{
	private static $counter = 0;
	public $id;
	public $name;
	public $param;
	public $result;
	public $done;
	public $datestamp;
	public $callback;
	public $thread_id;
	public $time_sent;
	
	const Run = 'run';
	const Quit = 'exit';
	const Ping = 'ping';
	
	function __construct($name, $callback = null)
	{
		$this->id = self::$counter++;
		$this->name = $name;
		$this->param = null;
		$this->result = null;
		$this->done = false;
		$this->datestamp = time();
		$this->callback = $callback;
		$this->thread_id = null;
	}
	
	function getId()
	{
		return $this->id;
	}
	
	static function getTotalCommands()
	{
		return self::$counter;
	}
}

?>