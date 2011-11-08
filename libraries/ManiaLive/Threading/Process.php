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
use ManiaLive\Utilities\Logger;
use ManiaLive\Database\SQLite\Connection;

/**
 * This class is running in it's own process and is
 * being instanciated by the thread_ignitor.php.
 * A Process is being represented by a Thread on the "server" side.
 * 
 * @author Florian Schnell
 */
class Process
{
	private $id;
	private $db;
	private $incomingJob;
	private $incomingJobCount;
	private $parent;
	
	function __construct($pid, $parent)
	{
		$this->id = $pid;
		$this->parent = $parent;
		
		// print first message from thread ...
		echo 'Thread started successfully!'.APP_NL;
		
		// when script terminates call this function to
		// update the thread's status ...
		register_shutdown_function(array($this, 'setClosed'));
		
		// connect to database ...
		$this->db = Tools::getDb($this->parent);
		if($this->db->isConnected())
			echo 'DB is connected, waiting for jobs ...'.APP_NL;
		
		$this->incomingJob = null;
		
		// get configuration ...
		\ManiaLive\Config\Config::forceInstance(Tools::getData($this->db, 'config'));
		\ManiaLive\Database\Config::forceInstance(Tools::getData($this->db, 'database'));
		\ManiaLive\Features\WebServices\Config::forceInstance(Tools::getData($this->db, 'wsapi'));
		\ManiaLive\Application\Config::forceInstance(Tools::getData($this->db, 'manialive'));
		\ManiaLive\DedicatedApi\Config::forceInstance(Tools::getData($this->db, 'server'));
		\ManiaLive\Threading\Config::forceInstance(Tools::getData($this->db, 'threading'));
		
		// thread state is ready ...
		$this->setReady();
	}
	
	/**
	 * Static function to set ready state.
	 * @param integer $pid
	 */
	function setReady()
	{
		$this->setBusy(0);
	}
	
	/**
	 * Static function to set busy state.
	 * @param integer $pid
	 */
	function setBusy($busy_flag = true)
	{
		$this->db->execute('UPDATE threads SET last_beat=%s, busy=%d WHERE proc_id=%d', time()+60, $busy_flag, $this->id);
	}
	
	/**
	 * Static function to set last thread activity.
	 * @param integer $pid
	 */
	function setLastBeat()
	{
		$this->db->execute('UPDATE threads SET last_beat=%s WHERE proc_id=%d', time()+60, $this->id);
	}
	
	/**
	 * Static function to set closed state.
	 * @param integer $pid
	 */
	function setClosed()
	{
		echo 'Closed Thread!'.APP_NL;
		$this->db->execute('UPDATE threads SET state=3 WHERE proc_id=%d', $this->id);
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param integer $cmdId
	 * @param mixed $returnValue
	 */
	function returnResult($cmdId, $returnValue)
	{
		//$return_value = array($return_value, ($this->incoming_job_count == 0));
		$this->db->execute('UPDATE cmd SET done=1, result=%s WHERE cmd_id=%d',
				$this->db->quote(base64_encode(serialize($returnValue))), $cmdId);
		echo 'Result saved rows: '.$this->db->affectedRows().APP_NL;
	}
	
	/**
	 * Checks the threading database Connection for new
	 * commands addressed to itself.
	 * If so, get them, process them and return the result.
	 */
	function getWork()
	{
		// query db for jobs ...
		$result = $this->db->query('SELECT cmd_id, cmd, param FROM cmd WHERE done=0 AND proc_id=%d ORDER BY datestamp DESC', $this->id);
		$this->incomingJobCount = $result->recordCount();
		
		if($this->incomingJobCount > 0)
			echo 'Incoming Jobs: '.$this->incomingJobCount.APP_NL;
		else
			return false;
		
		// process incoming jobs ...
		while($this->incomingJob = $result->fetchArray())
		{
			$this->incomingJobCount--;
			
			// this will save some writing ...
			$cmd = $this->incomingJob['cmd'];
			$cmdId = $this->incomingJob['cmd_id'];
			$cmdParam = $this->incomingJob['param'];
			
			echo 'Got Command: '.$cmd.APP_NL;
			
			switch($cmd)
			{
				case 'ping':
					// ping returns always true ...
					$this->returnResult($cmdId, true);
					break;
				case 'run':
					$this->setBusy();
					echo 'Processing Command ID: '.$cmdId.APP_NL;
					// process incoming job ...
					$job = unserialize(base64_decode($cmdParam));
					$this->returnResult($cmdId, $job->run());	
					$this->setReady();
					break;
				case 'exit': exit();
			}
		}

		// reset.
		$this->incomingJob = null;
		return true;
	}
}