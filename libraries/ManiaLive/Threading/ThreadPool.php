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

use ManiaLive\Event\Dispatcher;
use ManiaLive\Features\Tick\Listener as TickListener;
use ManiaLive\Features\Tick\Event as TickEvent;
use ManiaLive\Threading\Commands\Command;
use ManiaLive\Threading\Commands\QuitCommand;
use ManiaLive\Utilities\Console;
use ManiaLive\Utilities\Logger;

/**
 * Thread-Management is done here.
 * Add Threads and Jobs, the
 * ThreadPool will then assign Jobs
 * to the Threads to (hopefully) get
 * best performance ...
 * 
 * @author Florian Schnell
 */
class ThreadPool extends \ManiaLib\Utils\Singleton implements TickListener
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
		if(! (extension_loaded('SQLite') || extension_loaded('SQLite3')) )
		{
			Console::println("Threading will be disabled, enable the 'SQLite' extension on your system!");
			self::$threadingEnabled = false;
		}
		else
			self::$threadingEnabled = Config::getInstance()->enabled;
		
		// continue depending whether threading is enabled or not ...
		if(self::$threadingEnabled)
		{
			// keep manialive clean!
			// delete unused threading databases ...
			$files = glob(APP_ROOT.'/data/threading_*.db'); 
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
			$this->logger->write('Setting up Database ...');
			Tools::setupDb();
			
			// clean threads and states ...
			$this->logger->write('Removing old threads and commands ...');
			$this->clean();
		}
		else
		{
			// just print some information ...
			Console::println('[Attention] Threading disabled - this may cause performance issues!');
			$this->logger->write('Application started with threading disabled!');
			
			// create emulated thread number 0 ...
			$this->threads[0] = new Thread(0);
		}
		
		Dispatcher::register(TickEvent::getClass(), $this);
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
		if(self::$threadingEnabled)
		{
			// create thread ...
			$thread = Thread::Create();
			$this->threads[$thread->getId()] = $thread;
			$this->logger->write('Thread with ID '.$thread->getId().' has been started!');
			
			// increase thread count
			$this->threadsCount++;
			
			return $thread->getId();
		}
		else
			return 0;
	}
	
	/**
	 * Stops thread and removes it from
	 * the list of managed threads ...
	 * @param integer $pid
	 * @return bool
	 */
	function removeThread($id)
	{
		if(self::$threadingEnabled)
		{
			// check whether thread exists ...
			if(isset($this->threads[$id]))
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
		return isset($this->threads[$id]) ? $this->threads[$id] : null;
	}
	
	/**
	 * This is being called on startup to have
	 * a clean communication without old commands
	 * or threads.
	 */
	function clean()
	{
		// on startup remove every thread, job and data from the database!
		$this->database->execute('DELETE FROM threads');
		$this->logger->write('DB threads are cleaned, rows affected: '.$this->database->affectedRows());
		
		$this->database->execute('DELETE FROM cmd');
		$this->logger->write('DB cmds are cleaned, rows affected: '.$this->database->affectedRows());
		
		$this->database->execute('DELETE FROM data');
		$this->logger->write('DB data is cleaned, rows affected: '.$this->database->affectedRows());
	}
	
	/**
	 * This will add a command to the pool.
	 * The ThreadPool will then decide which Thread will take
	 * the work.
	 * @param ManiaLive\Threading\Commands\Command $command
	 */
	function addCommand(Command $command, $forceTid = false)
	{
		Console::printDebug('new Command has been added to the ThreadPool ...');
		
		// this command may only run on a specific thread ...
		if($forceTid !== false)
		{
			$this->threads[$forceTid]->addCommandToBuffer($command);
			return;
		}
		
		$sent = false;
		$threadPrev = false;
		$threadFirst = null;
		$threadId = null;
		foreach($this->threads as $thread)
		{
			$threadCur = $thread->getCommandCount();
			if($threadPrev !== false)
			{
				if($threadPrev > $threadCur)
				{
					$threadId = $thread->getId();
					break;
				}
			}
			else
				$threadFirst = $thread;
			$threadPrev = $threadCur;
		}
		
		if($threadId === null) 
			$threadId = $threadFirst->getId();
		
		Console::printDebug('Command has been assigned to thread #' . $threadId);
		
		$this->threads[$threadId]->addCommandToBuffer($command);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Features/Tick/ManiaLive\Features\Tick.Listener::onTick()
	 */
	function onTick()
	{
		if(!$this->running)
			return;
		
		if(self::$threadingEnabled)
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
			$start = time() + Config::getInstance()->sequentialTimeout;
			
			// get commands from each thread and execute them seqeuntially ...
			foreach($this->threads as $thread)
			{
				while($command = $thread->shiftCommand())
				{
					if($command->name == Command::Run)
					{
						$command->result = $command->param->run();
						if($command->callback != null)
						{
							// set threadid on 0 for the emulated main process ...
							if (is_callable($command->callback))
								call_user_func($command->callback, $command);
							else
								throw new \BadMethodCallException('The callback method that you defined for the currently processed Job could not be called!');
						}
					}
					// stop after specified time
					if(time() > $start)
						break;
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
			$thread->sendBufferedCommands();
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
			if(strpos($ex->getMessage(), 'database is locked') === false)
				throw $ex;
		}
		sqlite_busy_timeout($this->database->getHandle(), 60000);
		
		// no jobs that are finished ...
		if($results == null || !$results->recordAvailable())
			return;
		
		// do anything with them ...
		$ids = array();
		while($result = $results->fetchArray())
		{
			$threadId = $result['thread_id'];
			$cmdId = $result['cmd_id'];
			
			Console::printDebug('Got response for Command (' . $result['cmd'] . ') #' . $cmdId . ' finished on Thread #' . $threadId . '!');
			
			$result = unserialize(base64_decode($result['result']));
			if(isset($this->threads[$threadId]))
				$this->threads[$threadId]->receiveResponse($cmdId, $result);
			$ids[] = $cmdId;
		}
		
		// delete jobs from database ...
		$this->database->execute('DELETE FROM cmd WHERE done=1 AND cmd_id IN (%s)', implode(',', $ids));
	}
	
	/**
	 * This will restart threads that have timedout
	 * give them a new id and move them to the right place.
	 */
	function removeDeadThreads()
	{
		// modify local threads array
		foreach($this->threads as $id => $thread)
		{
			if($thread->getState() == Thread::STATE_DEAD)
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
		return self::$threadingEnabled ? $this->threadsCount : 1;
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
			foreach($this->threads as $id => $thread)
				$this->removeThread($id);
		}
		catch(\Exception $ex)
		{
			// uncaught errors in destructors are bad ...
		}
	}
}

?>