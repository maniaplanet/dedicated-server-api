<?php
/**
 * @copyright NADEO (c) 2010
 */

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
	public $threadId;
	public $timeSent;
	
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
		$this->threadId = null;
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