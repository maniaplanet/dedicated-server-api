<?php
namespace ManiaLive\Threading;

use ManiaLive\Config\Loader;

use ManiaLive\Threading\Commands\Command;
use ManiaLive\Threading\Commands\QuitCommand;

use ManiaLive\Utilities\Console;
use ManiaLive\Event\Dispatcher;
use ManiaLive\Threading\Tools;
use ManiaLive\Threading\Thread;
use ManiaLive\Utilities\Logger;
use ManiaLive\Database\SQLite\Connection;

/**
 * Thread-Management is done here.
 * Add Threads and Jobs, the
 * ThreadPool will then assign Jobs
 * to the Threads to (hopefully) get
 * best performance ...
 * @author Florian Schnell
 * @copyright 2010 NADEO
 */
class ThreadPool extends \ManiaLive\Utilities\Singleton implements \ManiaLive\Features\Tick\Listener
{
	private $threads;
	private $threads_pending;
	private $threads_count;
	private $database;
	private $logger;
	static $threading_enabled = false;
	static $threads_died_count = 0;
	static $avg_response_time = null;
	
	function __construct()
	{
		$this->threads = array();
		$this->threads_pending = array();
		$this->threads_count = 0;
		$this->logger = Logger::getLog('main', 'threading');
		$this->database = null;
		
		// check if library's enabled ...
		if (!extension_loaded('SQLite'))
		{
			Console::println("Threading will be disabled, you need to enable the 'SQLite' extension on your system!");
			self::$threading_enabled = false;
		}
		else
		{
			self::$threading_enabled = Loader::$config->threading->enabled;
		}
		
		// continue depending whether threading is enabled or not ...
		if (self::$threading_enabled)
		{
			$this->database = Tools::getDb();
			
			// setup database ...
			$this->logger->write("Setting up Database ...");
			Tools::setupDb();
			
			// clean threads and states ...
			$this->logger->write("Removing old threads and commands ...");
			$this->clean();
		}
		else
		{
			// just print some information ...
			Console::println('Attention: Threading disabled, trying to emulate - this will cause performance downgrades!');
			$this->logger->write("Application started with threading disabled!");
			
			// create emulated thread number 0 ...
			$main = new Thread(0);
			$this->threads[0] = $main;
		}
		
		// register me ...
		Dispatcher::register(\ManiaLive\Features\Tick\Event::getClass(), $this);
	}
	
	/**
	 * Creates a new Thread that will
	 * be able to process Jobs.
	 * Pay attention that depending on what
	 * system configuration you have
	 * more or less threads are making sense!
	 * @return integer
	 */
	function createThread()
	{
		if (self::$threading_enabled)
		{
			// create thread ...
			$thread = Thread::Create();
			$this->threads[$thread->getId()] = $thread;
			
			$this->logger->write("Thread with ID " . $thread->getId() . " has been started!");
			
			// increase thread count
			$this->threads_count++;
			
			return $thread->getId();
		}
		else
		{
			// if disabled all jobs run on emulated thread #0 ...
			return 0;
		}
	}
	
	/**
	 * Stops thread and removes it from
	 * the list of managed threads ...
	 * @param integer $pid
	 * @return bool
	 */
	function removeThread($id)
	{
		if (self::$threading_enabled)
		{
			// check whether thread exists ...
			if (array_key_exists($id, $this->threads))
			{
				$this->logger->write("Closing Thread with ID " . $id);
				
				// exit and remove from list ...
				$quit = new QuitCommand();
				$this->threads[$id]->sendCommand($quit);
				unset($this->threads[$id]);
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Returns the thread with the given ID.
	 * @return Thread
	 */
	function getThread($id)
	{
		if (isset($this->threads[$id]))
		{
			return $this->threads[$id];
		}
		else
		{
			return null;
		}
	}
	
	/**
	 * This is being called on startup to have
	 * a clean communication without old commands
	 * or threads.
	 */
	function clean()
	{
		// on startup remove every thread and job from the database!
		$this->database->execute("DELETE FROM threads");
		$this->logger->write("DB threads are cleaned, rows affected: " . $this->database->affectedRows());
		
		$this->database->execute("DELETE FROM cmd");
		$this->logger->write("DB cmd are cleaned, rows affected: " . $this->database->affectedRows());
	}
	
	/**
	 * This will add a command to the pool.
	 * The ThreadPool will then decide which Thread will take
	 * the work.
	 * @param ManiaLive\Threading\Commands\Command $command
	 * @throws WrongTypeException
	 */
	function addCommand(Command $command, $force_tid = false)
	{
		Console::printDebug('new Command has been added to the ThreadPool ...');
		
		// this command may only run on a specific thread ...
		if ($force_tid !== false)
		{
			$this->threads[$force_tid]->addCommandToBuffer($command);
			return;
		}
		
		$sent = false;
		$thread_prev = false;
		$thread_first = null;
		$thread_id = null;
		foreach ($this->threads as $thread)
		{
			$thread_cur = $thread->getCommandCount();
			if ($thread_prev !== false)
			{
				if ($thread_prev > $thread_cur)
				{
					$thread_id = $thread->getId();
					break;
				}
			}
			else
			{
				$thread_first = $thread;
			}
			$thread_prev = $thread_cur;
		}
		
		if ($thread_id == null) $thread_id = $thread_first->getId();
		
		Console::printDebug('Command has been assigned to thread #' . $thread_id);
		
		$this->threads[$thread_id]->addCommandToBuffer($command);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Features/Tick/ManiaLive\Features\Tick.Listener::onTick()
	 */
	function onTick()
	{
		if (self::$threading_enabled)
		{
			// afterwards collect responses,
			// current jobs may response next tick ...
			$this->receiveResponses();
			
			// first send all jobs from the queue
			try
			{
				$this->sendCommands();
			}
			catch (ThreadDiedException $ex)
			{
				$this->removeDeadThreads();
			}
			catch (ThreadTimedOutExcpetion $ex)
			{
				$this->removeDeadThreads();
			}
		}
		else
		{
			// set maximal exec time
			$start = time() + Loader::$config->threading->sequential_timeout;
			
			// get commands from each thread and execute them seqeuntially ...
			foreach ($this->threads as $thread)
			{
				while ($command = $thread->shiftCommand())
				{
					if ($command->name == Command::Run)
					{
						// run the command and store result ...
						$command->result = $command->param->run();
						
						// then run callback function ...
						if ($command->callback != null)
						{
							// set threadid on 0 for the emulated main process ...
							if (is_callable($command->callback))
							{
								// build response ...
								call_user_func($command->callback, $command);
							}
							else
							{
								throw new \BadMethodCallException('The callback method that you defined for the currently processed Job could not be called!');
							}
						}
					}
					
					// stop after specified time
					if (time() > $start)
					{
						break;
					}
				}
			}
		}
	}
	
	/**
	 * Sends each Thread's commands to the specified
	 * Process.
	 */
	function sendCommands()
	{
		foreach ($this->threads as $thread)
		{
			$thread->sendBufferedCommands();
		}
	}
	
	/**
	 * Receives the responses for all threads
	 * and delivers them.
	 */
	function receiveResponses()
	{
		$results = null;
		
		// try to fetch results from the database ...
		sqlite_busy_timeout($this->database->getHandle(), 100);
		try
		{
			$results = $this->database->query("SELECT proc_id, thread_id, cmd, cmd_id, result FROM cmd WHERE done=1");
		}
		catch (\Exception $ex)
		{
			if (strpos($ex->getMessage(), 'database is locked') === false)
				throw $ex;
		}
		sqlite_busy_timeout($this->database->getHandle(), 60000);
		
		// no jobs that are finished ...
		if ($results == null || !$results->recordAvailable())
			return;
		
		// do anything with them ...
		$ids = array();
		while ($result = $results->fetchArray())
		{
			$tid = $result['thread_id'];
			$cid = $result['cmd_id'];
			
			Console::printDebug('Got response for Command (' . $result['cmd'] . ') #' . $cid . ' finished on Thread #' . $tid . '!');
			
			$result = unserialize(base64_decode($result['result']));
			if (array_key_exists($tid, $this->threads))
			{
				$this->threads[$tid]->receiveResponse($cid, $result);
			}
			$ids[] = $cid;
		}
		
		// build delete query ...
		$query = 'DELETE FROM cmd WHERE done=1 AND cmd_id IN (' . implode(',', $ids) . ')';
		
		// delete jobs from database ...
		$this->database->execute($query);
	}
	
	/**
	 * This will restart threads that have timedout
	 * give them a new id and move them to the right place.
	 */
	function removeDeadThreads()
	{
		// modify local threads array
		foreach ($this->threads as $id => $thread)
		{
			if ($thread->getState() == 4)
			{
				self::$threads_died_count++;
				
				Console::printDebug('Detected dead Thread with ID ' . $thread->getId() . ' - running Process #' . $thread->getPid() . '!');
				
				$thread->restart();
				
				Console::printDebug('Restarted Thread with ID ' . $thread->getId() . ' - assigned Process #' . $thread->getPid() . ' ...');
			}
		}
	}
	
	/**
	 * Return number of currently running Threads.
	 * @return integer
	 */
	function getThreadCount()
	{
		return $this->threads_count;
	}
	
	function __destruct()
	{
		$this->logger->write('ThreadPool is being deleted, stopping all threads!');
		
		// tell every thread to shut down ...
		foreach ($this->threads as $id => $thread)
		{
			$this->removeThread($id);
		}
	}
}

class Exception extends \Exception {}

class NotSupportedException extends Exception {}

class WrongTypeException extends Exception {}
?>