<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 314 $:
 * @author      $Author: martin.gwendal $:
 * @date        $Date: 2012-01-03 14:16:20 +0100 (mar., 03 janv. 2012) $:
 */

namespace ManiaLive\Threading;

use ManiaLive\Database\Connection;
use ManiaLive\Utilities\Console;

/**
 * This class is running in it's own process and is being instanciated by the thread_ignitor.php.
 * It's a Singleton so tasks executed on it can use setData() and getData()
 */
class Thread extends \ManiaLib\Utils\Singleton
{
	private $threadId;
	private $parentId;
	private $database;
	private $taskBuffer = array();

	protected function __construct()
	{
		global $argv;
		$this->threadId = intval($argv[1]);
		$this->parentId = intval($argv[2]);
		$this->initDatabase();

		Console::println('Thread started successfully!');

		if($this->database->isConnected())
			Console::println('DB is connected, waiting for jobs ...');
	}
	
	private function initDatabase()
	{
		$this->database = Connection::getConnection('threading_'.$this->parentId, null, null, null, 'SQLite');

		// load configs from DB
		$configs = array(
			'config'    => \ManiaLive\Config\Config::getInstance(),
			'database'  => \ManiaLive\Database\Config::getInstance(),
			'wsapi'     => \ManiaLive\Features\WebServices\Config::getInstance(),
			'manialive' => \ManiaLive\Application\Config::getInstance(),
			'server'    => \ManiaLive\DedicatedApi\Config::getInstance(),
			'threading' => Config::getInstance()
		);
		$this->database->getHandle()->busyTimeout(2000);
		foreach($configs as $dbName => $instance)
		{
			$data = $this->getData($dbName);
			if($data)
				foreach((array)$data as $key => $value)
					$instance->$key = $value;
		}
	}
	
	function setData($key, $value)
	{
		$this->database->execute(
				'INSERT INTO data (name, value) VALUES (%s, %s)',
				$this->database->quote($key),
				$this->database->quote(base64_encode(serialize($value))));
		
		return $this->database->affectedRows() > 0;
	}
	
	function getData($key, $default=null)
	{
		$result = $this->database->query('SELECT value FROM data WHERE name=%s', $this->database->quote($key));
			
		if($result->recordAvailable())
			return unserialize(base64_decode($result->fetchScalar()));
		else
			return $default;
	}
	
	function run()
	{
		while(true)
		{
			$task = $this->nextTask();
			
			if($task)
			{
				$startTime = microtime(true);
				$result = $task['task']->run();
				$timeTaken = microtime(true) - $startTime;
				
				$this->database->getHandle()->busyTimeout(5000);
				$this->database->execute(
						'UPDATE commands SET result=%s, timeTaken=%f WHERE commandId=%d',
						$this->database->quote(base64_encode(serialize($result))), $timeTaken, $task['commandId']);
			}
			else
				sleep(1);
			
			if(!$this->isParentRunning())
				exit();
		}
	}
	
	private function nextTask()
	{
		if(empty($this->taskBuffer))
		{
			try
			{
				$this->database->getHandle()->busyTimeout(100);
				$tasks = @$this->database->query('SELECT commandId, task FROM commands WHERE threadId=%d AND result IS NULL ORDER BY commandId ASC', $this->threadId);
			}
			catch (\Exception $ex)
			{
				if(strpos($ex->getMessage(), 'database is locked') === false)
					throw $ex;
				return;
			}
			
			while( ($task = $tasks->fetchAssoc()) )
			{
				$task['task'] = unserialize(base64_decode($task['task']));
				$this->taskBuffer[] = $task;
			}
			Console::println('Incoming Tasks: '.count($this->taskBuffer));
		}
		
		return array_shift($this->taskBuffer);
	}
	
	private function isParentRunning()
	{
		// Unix case
		if(stripos(PHP_OS, 'WIN') !== 0)
		{
			exec('ps '.$this->parentId, $output, $result);

			if(count($output) >= 2)
				return strpos($output[1], 'bootstrapper.php') !== false;
			return false;
		}
		// Windows case
		else
		{
			exec('tasklist /FI "PID eq '.$this->parentId.'"', $output, $result);

			if(count($output) >= 4)
				return strpos($output[3], 'php.exe') !== false;
			return false;
		}
	}
}