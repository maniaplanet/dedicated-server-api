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
		$options = getopt(null, array('threadId:', 'parentId:'));
		$this->threadId = (int) $options['threadId'];
		$this->parentId = (int) $options['parentId'];
		$this->initDatabase();

		Console::println('Thread started successfully!');

		if($this->database->isConnected()) Console::println('DB is connected, waiting for jobs ...');
	}

	private function initDatabase()
	{
		$options = getopt(null, array('dbHost::', 'dbPort::', 'dbUsername::', 'dbPassword::', 'dbDatabase::'));

		$dbConfig = \ManiaLive\Database\Config::getInstance();
		$dbConfig->host = array_key_exists('dbHost', $options) ? $options['dbHost'] : $dbConfig->host;
		$dbConfig->port = array_key_exists('dbPort', $options) ? $options['dbPort'] : $dbConfig->port;
		$dbConfig->username = array_key_exists('dbUsername', $options) ? $options['dbUsername'] : $dbConfig->username;
		$dbConfig->password = array_key_exists('dbPassword', $options) ? $options['dbPassword'] : $dbConfig->password;
		$dbConfig->database = array_key_exists('dbDatabase', $options) ? $options['dbDatabase'] : $dbConfig->database
		;
		$this->database = Connection::getConnection($dbConfig->host, $dbConfig->username, $dbConfig->password,
				$dbConfig->database, 'MySQL', $dbConfig->port);
		// load configs from DB
		$configs = array(
			'config' => \ManiaLive\Config\Config::getInstance(),
			'database' => $dbConfig,
			'wsapi' => \ManiaLive\Features\WebServices\Config::getInstance(),
			'manialive' => \ManiaLive\Application\Config::getInstance(),
			'server' => \ManiaLive\DedicatedApi\Config::getInstance(),
			'threading' => Config::getInstance()
		);
		foreach($configs as $dbName => $instance)
		{
			$data = $this->getData($dbName);
			if($data) foreach((array) $data as $key => $value)
					$instance->$key = $value;
		}
	}

	function setData($key, $value)
	{
		$this->database->execute(
			'INSERT INTO data (parentId, name, value) VALUES (%d, %s, %s)', $this->parentId, $this->database->quote($key),
			$this->database->quote(serialize($value)));

		return $this->database->affectedRows() > 0;
	}

	function getData($key, $default = null)
	{
		$result = $this->database->query('SELECT value FROM data WHERE name=%s AND parentId = %d',
			$this->database->quote($key), $this->parentId);

		if($result->recordAvailable()) return unserialize($result->fetchScalar());
		else return $default;
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
				echo $this->database->quote(serialize($result))."\n";

				$this->database->execute(
					'UPDATE commands SET result=%s, timeTaken=%f WHERE commandId=%d AND parentId = %d',
					$this->database->quote(serialize($result)), $timeTaken, $task['commandId'], $this->parentId);
			}
			else sleep(1);

			if(!$this->isParentRunning()) exit();
		}
	}

	private function nextTask()
	{
		if(empty($this->taskBuffer))
		{
			$tasks = $this->database->query('SELECT commandId, task FROM commands WHERE threadId=%d AND result IS NULL AND parentId = %d ORDER BY commandId ASC',
				$this->threadId, $this->parentId);
			Console::println('Incoming Tasks: '.$tasks->recordCount());
			while(($task = $tasks->fetchAssoc()))
			{
				$task['task'] = unserialize($task['task']);
				$this->taskBuffer[] = $task;
			}
		}

		return array_shift($this->taskBuffer);
	}

	private function isParentRunning()
	{
		// Unix case
		if(stripos(PHP_OS, 'WIN') !== 0)
		{
			exec('ps '.$this->parentId, $output, $result);

			if(count($output) >= 2) return strpos($output[1], 'bootstrapper.php') !== false;
			return false;
		}
		// Windows case
		else
		{
			exec('tasklist /FI "PID eq '.$this->parentId.'"', $output, $result);

			if(count($output) >= 4) return strpos($output[3], 'php.exe') !== false;
			return false;
		}
	}

}