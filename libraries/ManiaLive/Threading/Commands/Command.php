<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 1709 $:
 * @author      $Author: svn $:
 * @date        $Date: 2011-01-07 14:06:13 +0100 (ven., 07 janv. 2011) $:
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