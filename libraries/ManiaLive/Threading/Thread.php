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

/**
 * This class is running in it's own process and is being instanciated by the thread_ignitor.php.
 * It's a Singleton so tasks executed on it can use setData() and getData()
 */
class Thread extends \ManiaLib\Utils\Singleton
{
	private $threadId;
	private $parentId;
	private $database;
	private $logger;
	private $taskBuffer = array();

	protected function __construct()
	{
		$options = getopt(null, array('threadId:', 'parentId:'));
		$this->threadId = (int) $options['threadId'];
		$this->parentId = (int) $options['parentId'];
		$this->initDatabase();
		$this->logger = \ManiaLive\Utilities\Logger::getLog('threading');

		$this->logger->write('Thread started successfully!', array('Process #'.$this->parentId.'.'.$this->threadId));
		if($this->database->isConnected())
			$this->logger->write('DB is connected, waiting for jobs ...', array('Process #'.$this->parentId.'.'.$this->threadId));
	}

	private function initDatabase()
	{
		$options = getopt(null, array('dbHost::', 'dbPort::', 'dbUsername::', 'dbPassword::', 'dbDatabase::'));

		$dbConfig = \ManiaLive\Database\Config::getInstance();
		foreach($options as $key => $value)
			$dbConfig->{lcfirst(substr($key, 2))} = $value;
		
		$this->database = Connection::getConnection(
				$dbConfig->host,
				$dbConfig->username,
				$dbConfig->password,
				$dbConfig->database,
				'MySQL',
				$dbConfig->port
			);
		// load configs from DB
		$configs = array(
			'config' => \ManiaLive\Config\Config::getInstance(),
			'wsapi' => \ManiaLive\Features\WebServices\Config::getInstance(),
			'manialive' => \ManiaLive\Application\Config::getInstance(),
			'server' => \ManiaLive\DedicatedApi\Config::getInstance(),
			'threading' => Config::getInstance()
		);
		foreach($configs as $dbName => $instance)
		{
			$data = $this->getData($dbName, array());
			foreach((array) $data as $key => $value)
				$instance->$key = $value;
		}
	}

	function setData($key, $value)
	{
		$this->database->execute(
				'INSERT INTO ThreadingData(parentId, name, value) VALUES (%d, %s, %s)',
				getmypid(),
				$this->database->quote($key),
				$this->database->quote(base64_encode(serialize($value)))
			);

		return $this->database->affectedRows() > 0;
	}

	function getData($key, $default = null)
	{
		$result = $this->database->execute(
				'SELECT value FROM ThreadingData WHERE name=%s AND parentId=%d',
				$this->database->quote($key),
				getmypid()
			);

		return $result->recordAvailable() ? unserialize(base64_decode($result->fetchSingleValue())) : $default;
	}

	function run()
	{
		while(true)
		{
			$task = $this->nextTask();

			if($task)
			{
				$this->logger->write('Executing Command #'.$task['commandId'].'...', array('Process #'.$this->parentId.'.'.$this->threadId));
				
				$startTime = microtime(true);
				$result = $task['task']->run();
				$timeTaken = (microtime(true) - $startTime) * 1000;

				$this->database->execute(
						'UPDATE ThreadingCommands SET result=%s, timeTaken=%f WHERE commandId=%d AND parentId=%d',
						$this->database->quote(base64_encode(serialize($result))),
						$timeTaken,
						$task['commandId'],
						$this->parentId
					);
				
				$this->logger->write('Command #'.$task['commandId'].' done in '.round($timeTaken, 3).' ms!', array('Process #'.$this->parentId.'.'.$this->threadId));
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
			$tasks = $this->database->execute(
					'SELECT commandId, task FROM ThreadingCommands WHERE threadId=%d AND result IS NULL AND parentId=%d ORDER BY commandId ASC',
					$this->threadId,
					$this->parentId
				);
			
			while(($task = $tasks->fetchAssoc()))
			{
				$task['task'] = unserialize(base64_decode($task['task']));
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
			exec('ps '.$this->parentId, $output);
			return count($output) >= 2 && strpos($output[1], 'bootstrapper.php') !== false;
		}
		// Windows case
		else
		{
			exec('tasklist /FI "PID eq '.$this->parentId.'"', $output);
			return count($output) >= 4 && strpos($output[3], 'php.exe') !== false;
		}
	}

}