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
 * 
 * @author Florian Schnell
 */
class ThreadPool extends \ManiaLib\Utils\Singleton implements \ManiaLive\Features\Tick\Listener
{
	private $running;
	private $threads;
	private $threadsPending;
	private $threadsCount;
	private $database;
	private $logger;
	
	static $threadingEnabled = false;
	static $threadsDiedCount = 0;
	static $avgResponseTime = null;
	
	function __construct()
	{
		$this->running = false;
		$this->threads = array();
		$this->threadsPending = array();
		$this->threadsCount = 0;
		$this->logger = Logger::getLog('main', 'threading');
		$this->database = null;
		
		// check if library's enabled ...
		if (!extension_loaded('SQLite'))
		{
			Console::println("Threading will be disabled, enable the 'SQLite' extension on your system!");
			self::$threadingEnabled = false;
		}
		else
		{
			self::$threadingEnabled = Loader::$config->threading->enabled;
		}
		
		// continue depending whether threading is enabled or not ...
		if (self::$threadingEnabled)
		{
			// keep manialive clean!
			// delete unused threading databases ...
			$files = glob(APP_ROOT . '/data/threading_*.db'); 
			if(is_array($files))
			{
			    foreach($files as $file)
			    {
				    try
				    {
					    unlink($file);
				    }
				    catch (\Exception $e) {}
			    }
			}
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
			Console::println('[Attention] Threading disabled - this may cause performance issues!');
			$this->logger->write("Application started with threading disabled!");
			
			// create emulated thread number 0 ...
			$this->threads[0] = new Thread(0);
		}
		
		// register me ...
		Dispatcher::register(\ManiaLive\Features\Tick\Event::getClass(), $this);
	}
	
	/**
	 * Starts thread processing.
	 */
	function run()
	{
		$this->running = true;
	}
	
	/**
	 * Stops thread processing.
	 */
	function stop()
	{
		$this->running = false;
	}
	
	/**
	 * @return \ManiaLive\Database\Connection
	 */
	function getDatabase()
	{
		return $this->database;
	}
	
	/**
	 * @return \ManiaLive\Threading\ThreadPool
	 * @throws \BadMethodCallException
	 */
	static function getInstance()
	{
		return parent::getInstance();
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
		if (self::$threadingEnabled)
		{
			// create thread ...
			$thread = Thread::Create();
			$this->threads[$thread->getId()] = $thread;
			
			$this->logger->write("Thread with ID " . $thread->getId() . " has been started!");
			
			// increase thread count
			$this->threadsCount++;
			
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
		if (self::$threadingEnabled)
		{
			// check whether thread exists ...
			if (array_key_exists($id, $this->threads))
			{
				$this->logger->write("Closing Thread with ID " . $id);
				
				// exit and remove from list ...
				$quit = new QuitCommand();
				$this->threads[$id]->sendCommand($quit);
				unset($this->threads[$id]);
				
				$this->threadsCount--;
				
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
		return (isset($this->threads[$id]) ? $this->threads[$id] : null);
	}
	
	/**
	 * This is being called on startup to have
	 * a clean communication without old commands
	 * or threads.
	 */
	function clean()
	{
		// on startup remove every thread, job and data from the database!
		$this->database->execute("DELETE FROM threads");
		$this->logger->write("DB threads are cleaned, rows affected: " . $this->database->affectedRows());
		
		$this->database->execute("DELETE FROM cmd");
		$this->logger->write("DB cmds are cleaned, rows affected: " . $this->database->affectedRows());
		
		$this->database->execute("DELETE FROM data");
		$this->logger->write("DB data is cleaned, rows affected: " . $this->database->affectedRows());
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
		
		if ($thread_id === null) 
		{
			$thread_id = $thread_first->getId();
		}
		
		Console::printDebug('Command has been assigned to thread #' . $thread_id);
		
		$this->threads[$thread_id]->addCommandToBuffer($command);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Features/Tick/ManiaLive\Features\Tick.Listener::onTick()
	 */
	function onTick()
	{
		if (!$this->running)
		{
			return;
		}
		
		if (self::$threadingEnabled)
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
			sqlite_busy_timeout($this->database->getHandle(), 60000);
			if (strpos($ex->getMessage(), 'database is locked') === false)
			{
				throw $ex;
			}
		}
		sqlite_busy_timeout($this->database->getHandle(), 60000);
		
		// no jobs that are finished ...
		if ($results == null || !$results->recordAvailable())
		{
			return;
		}
		
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
				self::$threadsDiedCount++;
				$this->threadsCount--;
				
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
		if (!self::$threadingEnabled)
		{
			return 1;
		}
		else
		{
			return $this->threadsCount;
		}
	}
	
	/**
	 * Closes all threads when program terminates
	 * or the instance of thread pool gets destroyed.
	 */
	function __destruct()
	{
		try
		{
			$this->logger->write('ThreadPool is being deleted, stopping all threads!');
			
			// tell every thread to shut down ...
			foreach ($this->threads as $id => $thread)
			{
				$this->removeThread($id);
			}
		}
		catch (\Exception $ex)
		{
			// uncaught errors in destructors are bad ...
		}
	}
}

class Exception extends \Exception {}

class NotSupportedException extends Exception {}

class WrongTypeException extends Exception {}
?>