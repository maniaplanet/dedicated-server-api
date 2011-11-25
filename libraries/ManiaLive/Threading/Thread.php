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

use ManiaLive\Database\SQLite\Connection;
use ManiaLive\Event\Dispatcher;
use ManiaLive\Threading\Commands\Command;
use ManiaLive\Threading\Commands\QuitCommand;
use ManiaLive\Threading\Commands\PingCommand;
use ManiaLive\Utilities\Logger;

/**
 * Each Thread represents its own process.
 * Infact this is not multithreading, but multitasking
 * using the OS' abilities.
 * This is why performance and stability can vary depending
 * on your system-type.
 *
 * @author Florian Schnell
 */
class Thread
{
	const STATE_UNKNOWN = 0;
	const STATE_READY   = 1;
	const STATE_PENDING = 2;
	const STATE_CLOSED  = 3;
	const STATE_DEAD    = 4;
	/**
	 * @var int
	 */
	private $state;
	/**
	 * database connectio the thread needs to
	 * communicate with the process.
	 * @var Connection
	 */
	static $db;
	/**
	 * ID of the Thread.
	 * @var integer
	 */
	private $id;
	/**
	 * ID of the Process behind the Thread.
	 * @var integer
	 */
	private $pid;
	/**
	 * Helps to write thread logfile
	 * @var Logger
	 */
	private $log;
	/**
	 * Intern list of which commands are currently
	 * executed on the Process.
	 * @var array[Command]
	 */
	private $commands;
	/**
	 * Number of the commands that are still being
	 * processed.
	 * @var integer
	 */
	private $commandCount;
	/**
	 * Commands that were sent to the server, but
	 * of which we didn't get any response yet.
	 * @var array[Command]
	 */
	private $commandsSent;
	/**
	 * How many commands have been sent yet without
	 * getting a response?
	 * @var integer
	 */
	private $commandsSentCount;
	/**
	 * When has the ping signal been sent to the
	 * process?
	 * @var integer
	 */
	private $pingStarted;
	/**
	 * When did the thread enter busy state?
	 * @var integer
	 */
	private $busyStarted;
	/**
	 * Counts Process instances
	 * @var integer
	 */
	static $pcounter = 0;
	/**
	 * Counts thread instances
	 * @var integer
	 */
	static $tcounter = 0;

	function __construct($id) {

		$this->id = $id;
		$this->log = Logger::getLog($this->getPid(), 'threading');

		// commands waiting ...
		$this->commands = array();
		$this->commandCount = 0;

		// commands currently being processed ...
		$this->commandsSent = array();
		$this->commandsSentCount = 0;

		// database stuff ...
		if(ThreadPool::$threadingEnabled)
		{
			// start process and get its id ...
			$this->pid = self::LaunchProcess();

			// create thread in database ...
			self::$db = Tools::getDb();
			self::$db->execute('INSERT INTO threads(proc_id, last_beat) VALUES(%d, %s);', $this->pid, time());
		}

		// dispatch event for starting thread ...
		Dispatcher::dispatch(new Event(Event::ON_THREAD_START, $this));
	}

	/**
	 * Factory function that creates new Threads for us.
	 * - and this means also starting the linked process -
	 * @return Thread
	 */
	static function Create()
	{
		self::$tcounter++;
		$t = new Thread(self::$tcounter);

		// check whether process is running ...
		$t->ping();

		return $t;
	}

	/**
	 * This launches a new process with the given id.
	 * @param integer $id
	 * @throws Exception
	 */
	static function LaunchProcess()
	{
		$pid = ++self::$pcounter;
		$log = Logger::getLog($pid, 'threading');
		$config = \ManiaLive\Config\Config::getInstance();

		$command =
			// add path to the php executeable ...
			'"'.Config::getInstance()->phpPath.'" '.
			// add thread_ignitor.php as argument ...
			'"'.__DIR__.DIRECTORY_SEPARATOR.'thread_ignitor.php" '.
			// forward output stream to file ...
			'>"'.$config->logsPath.DIRECTORY_SEPARATOR.$config->logsPrefix.'_threading_proc_'.$pid.'.txt" '.
			// add id and this process id as arguments for thread_ignitor.php ...
			$pid.' '.getmypid();
			// this will launch a new process on windows ...

		if(stripos(PHP_OS, 'WIN') === 0)
			// start command, run in background and asign processid on Windows...
			$command = 'start /B "manialive_thread_'. $pid. '" '.$command;
		else
			// start in background on Linux...
			$command .= '&';

		$log->write('Trying to start process using command:'.PHP_EOL.$command);

		// try to start process ...
		$phandle = popen($command, 'r');
		if($phandle === false)
			throw new Exception('Process with ID #' . $pid . ' could not be started!');
		pclose($phandle);

		return $pid;
	}

	/**
	 * This tells us depending on the last
	 * response of the Process whether it is still running.
	 * @return bool
	 */
	function isActive ()
	{
        return $this->state == self::STATE_READY;
	}

	/**
	 * Method to check whether a Process is currently
	 * busy or if it can take a new task.
	 * @return bool
	 */
	function isBusy()
	{
		return $this->commandsSentCount > 0;
	}

	/**
	 * Instantly sends the Command with least
	 * reliability.
	 * @param $command
	 */
	function sendCommand(Command $command)
	{
		$command->timeSent = microtime(true);
		self::$db->execute(
				'INSERT INTO cmd(proc_id, thread_id, cmd, cmd_id, param, done, datestamp) VALUES(%d, %d, %s, %d, %s, 0, %s);',
				$this->pid, $this->id, self::$db->quote($command->name), $command->getId(),
				self::$db->quote(base64_encode(serialize($command->param))), time());

		$this->commandsSentCount++;
		$this->commandsSent[$command->getId()] = $command;
	}

	/**
	 * Adds Command to an intern queue that will
	 * be sent as soon as possible, but with high reliability!
	 * @param $command
	 */
	function addCommandToBuffer(Command $command)
	{
		$this->commandCount++;
		$command->threadId = $this->id;
		$command->timeSent = microtime(true);
		$this->commands[$command->getId()] = $command;
	}

	/**
	 * Processes the queue of buffered Commands.
	 * Only if possible and if the Thread/Process is available.
	 * change: send a chunk only.
	 */
	function sendBufferedCommands()
	{
		// start running checks to see whether the server is able
		// to process any commands at the moment!

		// dont send commands if thread state is unkown
		// maybe it got killed? ping!
		$config = Config::getInstance();
		if($this->getState() == self::STATE_PENDING)
		{
			if(time() - $this->pingStarted > $config->pingTimeout)
			{
				$this->setState(self::STATE_DEAD);
				Dispatcher::dispatch(new Event(Event::ON_THREAD_DIES, $this));
				throw new ThreadDiedException('Thread has timed out!');
			}
			return;
		}

		// dont send commands if thread is busy
		// maybe it hung up, you can only wait and see ...
		if($this->isBusy())
		{
			if(time() - $this->busyStarted > $config->busyTimeout)
			{
				$this->setState(self::STATE_DEAD);
				Dispatcher::dispatch(new Event(Event::ON_THREAD_TIMES_OUT, $this));
				throw new ThreadTimedOutExcpetion('Thread is busy for too long!');
			}
			return;
		}

		// only continue if there are commands to be processed ...
		if($this->commandCount == 0)
		{
			// state is now unknown, next send will require a ping
			// to see whether process is still running.
			if($this->getState() == self::STATE_READY)
				$this->setState(self::STATE_UNKNOWN);
			return;
		}

		// if the thread is not ready then check
		if($this->getState() == self::STATE_UNKNOWN)
		{
			$this->ping();
			return;
		}

		// anything other than state 1 is not accepted.
		if($this->getState() != self::STATE_READY)
			return;

		// build query ...
		$query = '';
		$i = 0;

		foreach($this->commands as $command)
		{
			if(++$i > $config->chunkSize)
				break;

			self::$db->execute(
					'INSERT INTO cmd(proc_id, thread_id, cmd, cmd_id, param, done, datestamp) VALUES(%d, %d, %s, %d, %s, 0, %s);',
					$this->pid, $this->id, self::$db->quote($command->name), $command->getId(),
					self::$db->quote(base64_encode(serialize($command->param))), time());
			$this->commandsSent[$command->id] = $command;
			++$this->commandsSentCount;
			--$this->commandCount;
			unset($this->commands[$command->id]);
		}

		// thread is busy now
		$this->busyStarted = time();

		// current state is unkown ...
		$this->setState(self::STATE_UNKNOWN);
	}

	/**
	 * Command completed.
	 * @param integer $cmdId
	 * @param mixed $result
	 */
	function receiveResponse($cmdId, $result)
	{
		if(!isset($this->commandsSent[$cmdId]))
			return;

		$command = $this->commandsSent[$cmdId];
		$command->result = $result;
		$callback = $command->callback;
		unset($this->commandsSent[$cmdId]);
		$this->commandsSentCount--;

		$this->busyStarted = $this->isBusy() ? time() : null;
		$this->setState(self::STATE_READY);

		// callback
		if(is_callable($callback))
			call_user_func($callback, $command);

		// calculate average response time
		if(ThreadPool::$avgResponseTime == null)
			ThreadPool::$avgResponseTime = (microtime(true) - $command->timeSent);
		else
		{
			ThreadPool::$avgResponseTime += (microtime(true) - $command->timeSent);
			ThreadPool::$avgResponseTime /= 2;
		}
	}

	/**
	 * Sends a ping to the server to check its
	 * State.
	 */
	function ping()
	{
		// track time needed until there's a response ...
		$this->pingStarted = time();

		$this->setState(self::STATE_PENDING);
		$ping = new PingCommand();

		// send directly without check ...
		$this->sendCommand($ping);
	}

	/**
	 * Restarts thread in case of a timeout or
	 * any other purpose.
	 */
	function restart()
	{
		// tell current process to quit ...
		$command = new QuitCommand();
		$command->callback = array($this, 'exitDone');
		$this->sendCommand($command);

		// restart new process with new id ...
		$this->pid = self::LaunchProcess();
		$this->log = Logger::getLog($this->getPid(), 'threading');
		$this->busyStarted = time();
		$this->pingStarted = null;
		$this->commandsSent = array();
		$this->commandsSentCount = 0;
		$this->setState(self::STATE_UNKNOWN);
		$this->ping();

		// dispatch restart event ...
		Dispatcher::dispatch(new Event(Event::ON_THREAD_RESTART, $this));

		return $this->pid;
	}

	/**
	 * Sets the intern state of the Thread.
	 * @param integer $state
	 */
	function setState($state)
	{
		if($state >= self::STATE_UNKNOWN && $state <= self::STATE_DEAD)
			$this->state = $state;
	}

	/**
	 * @return integer
	 */
	function getState()
	{
		return $this->state;
	}

	/**
	 * Return the current amount of Commands that
	 * haven't been sent to the Process yet.
	 * @return integer
	 */
	function getCommandCount()
	{
		return $this->commandCount;
	}

	/**
	 * Return the first Command waiting in the queue
	 * and removes it.
	 */
	function shiftCommand()
	{
		return array_shift($this->commands);
	}

	/**
	 * Return the Process' ID.
	 * @return integer
	 */
	function getPid()
	{
		return $this->pid;
	}

	/**
	 * Returns the ID of the Thread.
	 * @return integer
	 */
	function getId()
	{
		return $this->id;
	}
}

// thread does not respond to a ping, though it is not busy
class ThreadDiedException extends \Exception {};

// a thread is busy and did not respond for a given amount of seconds
class ThreadTimedOutExcpetion extends \Exception {};
?>