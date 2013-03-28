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
use ManiaLive\Event\Dispatcher;
use ManiaLive\Features\Tick\Listener as TickListener;
use ManiaLive\Features\Tick\Event as TickEvent;
use ManiaLive\Utilities\Console;
use ManiaLive\Utilities\Logger;

final class ThreadHandler extends \ManiaLib\Utils\Singleton implements TickListener
{
	private $threadsCount = 0;
	private $threads = array();
	private $lastTick = array();
	private $buffers = array();
	private $pendings = array();
	private $tries = array();

	private $database;
	private $tick = 0;

	private $enabled = false;
	private $deadThreadsCount = 0;
	private $commandsCount = 0;
	private $commandsTotalTime = 0;
	private $commandsAverageTime = 0;

	protected function __construct()
	{
		$this->enabled = extension_loaded('mysql') && Config::getInstance()->enabled;

		if($this->enabled)
		{
			$this->setUpDatabase();
			$this->setData('config', \ManiaLive\Config\Config::getInstance());
			$this->setData('wsapi', \ManiaLive\Features\WebServices\Config::getInstance());
			$this->setData('manialive', \ManiaLive\Application\Config::getInstance());
			$this->setData('server', \ManiaLive\DedicatedApi\Config::getInstance());
			$this->setData('threading', \ManiaLive\Threading\Config::getInstance());
		}
		else
		{
			Logger::debug('Application started with threading disabled!', true, array('Process #'.getmypid()));
			$this->buffers[0] = array();
		}

		Dispatcher::register(TickEvent::getClass(), $this);
	}

	private function setUpDatabase()
	{
		$dbConfig = \ManiaLive\Database\Config::getInstance();
		$this->database = Connection::getConnection(
				$dbConfig->host,
				$dbConfig->username,
				$dbConfig->password,
				$dbConfig->database,
				'MySQL',
				$dbConfig->port
			);

		$this->database->execute(
				'CREATE TABLE IF NOT EXISTS `ThreadingProcesses` ('.
					'`parentId` INT(10) UNSIGNED NOT NULL,'.
					'`lastLive` DATETIME NOT NULL,'.
					'PRIMARY KEY (`parentId`)'.
				')'.
				'COLLATE=\'utf8_general_ci\''
			);

		$this->database->execute(
				'CREATE TABLE IF NOT EXISTS `ThreadingData` ('.
					'`parentId` INT(10) UNSIGNED NOT NULL,'.
					'`name` VARCHAR(255) NOT NULL,'.
					'`value` TEXT NOT NULL,'.
					'PRIMARY KEY (`parentId`, `name`)'.
				')'.
				'COLLATE=\'utf8_general_ci\''
			);

		$this->database->execute(
				'CREATE TABLE IF NOT EXISTS `ThreadingCommands` ('.
					'`parentId` INT(10) UNSIGNED NOT NULL,'.
					'`commandId` INT(10) UNSIGNED NOT NULL,'.
					'`threadId` INT(10) UNSIGNED NOT NULL,'.
					'`task` TEXT NOT NULL,'.
					'`result` TEXT NULL DEFAULT NULL,'.
					'`timeTaken` FLOAT UNSIGNED NULL DEFAULT NULL,'.
					'PRIMARY KEY (`parentId`, `commandId`),'.
					'INDEX `threadId` (`threadId`)'.
				')'.
				'COLLATE=\'utf8_general_ci\''
			);

		$deadPids = $this->database->execute(
				'SELECT parentId FROM ThreadingProcesses WHERE parentId=%d OR DATE_ADD(lastLive, INTERVAL 2 MINUTE) < NOW()',
				getmypid()
			)->fetchArrayOfSingleValues();
		if($deadPids)
		{
			$deadPids = implode(',', array_map('intval', $deadPids));
			$this->database->execute('DELETE FROM ThreadingProcesses WHERE parentId IN (%s)', $deadPids);
			$this->database->execute('DELETE FROM ThreadingData WHERE parentId IN (%s)', $deadPids);
			$this->database->execute('DELETE FROM ThreadingCommands WHERE parentId IN (%s)', $deadPids);
		}
		$this->database->execute('INSERT INTO ThreadingProcesses(parentId, lastLive) VALUES(%s, NOW())', getmypid());
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

	function launchThread()
	{
		if(!$this->enabled) return 0;

		$threadId = ++$this->threadsCount;
		$threadHandle = $this->spawnThread($threadId);
		if($threadHandle === false)
			throw new Exception('Thread #'.$threadId.' could not be started!');

		$this->threads[$threadId] = $threadHandle;
		$this->lastTick[$threadId] = $this->tick;
		$this->buffers[$threadId] = array();
		$this->pendings[$threadId] = array();
		Dispatcher::dispatch(new Event(Event::ON_THREAD_START, $threadId));
		Logger::debug('Thread #'.$threadId.' started!', true, array('Process #'.getmypid()));

		return $threadId;
	}

	private function spawnThread($threadId)
	{
		$config = \ManiaLive\Config\Config::getInstance();
		$dbConfig = \ManiaLive\Database\Config::getInstance();
		$outputFile = $config->logsPath.'/'.($config->logsPrefix ? $config->logsPrefix.'-' : '').'threading-error.txt';
		$descriptors = array(
			1 => array('file', $outputFile, 'a'),
			2 => array('file', $outputFile, 'a')
		);

		$args = array(
			'threadId' => $threadId,
			'parentId' => getmypid(),
			'dbHost' => $dbConfig->host,
			'dbPort' => $dbConfig->port,
			'dbUsername' => $dbConfig->username,
			'dbPassword' => $dbConfig->password,
			'dbDatabase' => $dbConfig->database
		);

		$command = '"'.Config::getInstance()->phpPath.'"  "'.__DIR__.DIRECTORY_SEPARATOR.'thread_ignitor.php"';
		foreach($args as $key => $value)
			$command .= ' --'.$key.'='.escapeshellarg($value);

		Console::printDebug('Trying to spawn Thread #'.$threadId.' using command: '.PHP_EOL.$command);
		return proc_open($command, $descriptors, $pipes, null, null, array('bypass_shell' => true));
	}

	function killThread($threadId)
	{
		if(!$this->enabled || !isset($this->threads[$threadId]))
			return;

		$threadHandle = $this->threads[$threadId];
		proc_terminate($threadHandle);
		proc_close($threadHandle);
		Dispatcher::dispatch(new Event(Event::ON_THREAD_KILLED, $threadId));
		Logger::debug('Thread #'.$threadId.' stopped', true, array('Process #'.getmypid()));

		$this->database->execute('DELETE FROM ThreadingCommands WHERE threadId=%d AND parentId=%d', $threadId, getmypid());

		unset($this->threads[$threadId]);
		unset($this->lastTick[$threadId]);
		unset($this->buffers[$threadId]);
		foreach($this->pendings[$threadId] as $commandId => $command)
			unset($this->tries[$commandId]);
		unset($this->pendings[$threadId]);
	}

	private function restartThread($threadId)
	{
		if(!$this->enabled || !isset($this->threads[$threadId]))
			return;

		$commandDiscarded = false;
		if(empty($this->pendings[$threadId]))
		{
			Looger::debug('Thread #'.$threadId.' died...', true, array('Process #'.getmypid()));
			Dispatcher::dispatch(new Event(Event::ON_THREAD_DIES, $threadId));
		}
		else
		{
			Looger::debug('Thread #'.$threadId.' timed out...', true, array('Process #'.getmypid()));
			Dispatcher::dispatch(new Event(Event::ON_THREAD_TIMES_OUT, $threadId));
			// If we already tried this command too many times, we discard it...
			$command = reset($this->pendings[$threadId]);
			$lastCommandId = $command->getId();
			if(++$this->tries[$lastCommandId] > Config::getInstance()->maxTries)
			{
				$this->database->execute(
						'DELETE FROM ThreadingCommands WHERE commandId=%d AND parentId=%d',
						$lastCommandId,
						getmypid()
					);
				unset($this->pendings[$threadId][$lastCommandId]);
				unset($this->tries[$lastCommandId]);
				Looger::debug('Command #'.$lastCommandId.' has been discarded after '.Config::getInstance()->maxTries.' unsuccessful tries...', true, array('Process #'.getmypid()));
				$commandDiscarded = true;
			}
		}

		// Respawning the thread
		$threadHandle = $this->threads[$threadId];
		proc_terminate($threadHandle);
		proc_close($threadHandle);
		++$this->deadThreadsCount;
		$this->threads[$threadId] = $this->spawnThread($threadId);
		$this->lastTick[$threadId] = $this->tick;
		Dispatcher::dispatch(new Event(Event::ON_THREAD_RESTART, $threadId));
		Logger::debug('Thread #'.$threadId.' restarted!', true, array('Process #'.getmypid()));

		if($commandDiscarded)
			$command->fail();
	}

	function countThreads()
	{
		return count($this->threads);
	}

	function countRestartedThreads()
	{
		return $this->deadThreadsCount;
	}

	function addTask($threadId, Runnable $task, $callback = null)
	{
		if(isset($this->buffers[$threadId])) $this->buffers[$threadId][] = new Command($task, $callback);
	}

	function isEnabled()
	{
		return $this->enabled;
	}

	function countFinishedCommands()
	{
		return $this->commandsCount;
	}

	function getAverageResponseTime()
	{
		return $this->commandsAverageTime;
	}

	function onTick()
	{
		if($this->enabled)
		{
			++$this->tick;
			$this->receiveResponses();
			$this->handleTimeOuts();
			$this->sendTasks();
			if($this->tick % 60 == 0)
				$this->database->execute('UPDATE ThreadingProcesses SET lastLive=NOW() WHERE parentId=%d', getmypid());
		}
		else
		{
			$startTime = microtime(true);
			$stopTime = $startTime + Config::getInstance()->sequentialTimeout;

			while($command = array_shift($this->buffers[0]))
			{
				$result = $command->getTask()->run();
				$endTime = microtime(true);
				$command->setResult($result, $endTime - $startTime);

				// stop after specified time
				if($endTime > $stopTime) break;

				$startTime = microtime(true);
			}
		}
	}

	private function receiveResponses()
	{
		$results = $this->database->execute(
				'SELECT commandId, threadId, result, timeTaken FROM ThreadingCommands WHERE result IS NOT NULL AND parentId=%d',
				getmypid()
			);

		if(!$results->recordAvailable()) return;

		$ids = array();
		while($result = $results->fetchArray())
		{
			$commandId = (int) $result['commandId'];
			$threadId = (int) $result['threadId'];
			$timeTaken = (float) $result['timeTaken'];

			Console::printDebug('Got response for Command #'.$commandId.' finished by Thread #'.$threadId.' in '.round($timeTaken, 3).' ms!');

			if(isset($this->pendings[$threadId][$commandId]))
			{
				$command = $this->pendings[$threadId][$commandId];
				$command->setResult(unserialize(base64_decode($result['result'])), $timeTaken);
				unset($this->pendings[$threadId][$commandId]);
				unset($this->tries[$commandId]);
			}
			if(isset($this->lastTick[$threadId])) $this->lastTick[$threadId] = $this->tick;
			++$this->commandsCount;
			$this->commandsTotalTime += $timeTaken;
			$this->commandsAverageTime = $this->commandsTotalTime / $this->commandsCount;

			$ids[] = $commandId;
		}

		$this->database->execute(
				'DELETE FROM ThreadingCommands WHERE commandId IN (%s) AND parentId=%d',
				implode(',', $ids),
				getmypid()
			);
	}

	private function handleTimeOuts()
	{
		foreach($this->lastTick as $threadId => $tick)
		{
			if(empty($this->pendings[$threadId]) && $this->tick - $tick > Config::getInstance()->busyTimeout / 2)
			{
				$threadStatus = proc_get_status($this->threads[$threadId]);
				if($threadStatus['running'])
					$this->lastTick[$threadId] = $this->tick;
				else
					$this->restartThread($threadId);
			}
			else if($this->tick - $tick > Config::getInstance()->busyTimeout)
				$this->restartThread($threadId);
		}
	}

	private function sendTasks()
	{
		$lines = array();
		foreach($this->buffers as $threadId => &$buffer)
			while($command = array_shift($buffer))
			{
				$commandId = $command->getId();
				$lines[] = sprintf(
						'(%d, %d, %d, %s)',
						$commandId,
						$threadId,
						getmypid(),
						$this->database->quote(base64_encode(serialize($command->getTask())))
					);
				$this->pendings[$threadId][$commandId] = $command;
				$this->tries[$commandId] = 1;
			}

		if(!empty($lines))
			$this->database->execute('INSERT INTO ThreadingCommands(commandId, threadId, parentId, task) VALUES '.implode(',', $lines));
	}

	function __destruct()
	{
		foreach($this->threads as $thread)
			proc_terminate($thread);
	}

}

?>
