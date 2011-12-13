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

use ManiaLive\Utilities\Console;

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
	private $parent;

	function __construct($pid, $parent)
	{
		$this->id = $pid;
		$this->parent = $parent;
		$this->db = Tools::getDb($this->parent);

		// load config from DB
		$configs = array(
			'config' => \ManiaLive\Config\Config::getInstance(),
			'database' => \ManiaLive\Database\Config::getInstance(),
			'wsapi' => \ManiaLive\Features\WebServices\Config::getInstance(),
			'manialive' => \ManiaLive\Application\Config::getInstance(),
			'server' => \ManiaLive\DedicatedApi\Config::getInstance(),
			'threading' => Config::getInstance()
		);
		foreach($configs as $dbName => $instance)
		{
			$data = Tools::getData($this->db, $dbName);
			if($data)
				foreach((array)$data as $key => $value)
					$instance->$key = $value;
		}

		// print first message from thread ...
		Console::println('Thread started successfully!');

		// when script terminates call this function to
		// update the thread's status ...
		register_shutdown_function(array($this, 'setClosed'));

		if($this->db->isConnected())
			Console::println('DB is connected, waiting for jobs ...');

		$this->incomingJob = null;
		$this->setReady();
	}

	/**
	 * Static function to set ready state.
	 * @param integer $pid
	 */
	function setReady()
	{
		$this->setBusy(false);
	}

	/**
	 * Static function to set busy state.
	 * @param integer $pid
	 */
	function setBusy($isBusy = true)
	{
		$this->db->execute('UPDATE threads SET last_beat=%s, busy=%d WHERE proc_id=%d', time()+60, $isBusy, $this->id);
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
		Console::println('Closed Thread!');
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
		$this->db->execute(
				'UPDATE cmd SET done=1, result=%s WHERE cmd_id=%d',
				$this->db->quote(base64_encode(serialize($returnValue))), $cmdId);
		Console::println('Result saved rows: '.$this->db->affectedRows());
	}

	/**
	 * Checks the threading database Connection for new
	 * commands addressed to itself.
	 * If so, get them, process them and return the result.
	 */
	function getWork()
	{
		// query db for jobs ...
		$result = $this->db->query('SELECT cmd_id, cmd, param FROM cmd WHERE done=0 AND proc_id=%d ORDER BY datestamp ASC, cmd_id ASC', $this->id);
		
		if($result->recordCount() > 0)
			Console::println('Incoming Jobs: '.$result->recordCount());
		else
			return false;

		// process incoming jobs ...
		while($this->incomingJob = $result->fetchArray())
		{
			Console::println('Got Command: '.$this->incomingJob['cmd']);
			
			$cmdId = $this->incomingJob['cmd_id'];
			switch($this->incomingJob['cmd'])
			{
				case 'ping':
					// ping returns always true ...
					$this->returnResult($cmdId, true);
					break;
				case 'run':
					$this->setBusy();
					Console::println('Processing Command ID: '.$cmdId);
					// process incoming job ...
					$job = unserialize(base64_decode($this->incomingJob['param']));
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