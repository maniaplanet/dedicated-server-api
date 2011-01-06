<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\Threading;

use ManiaLive\Utilities\Logger;
use ManiaLive\Database\SQLite\Connection;

/**
 * Some useful functions shared among
 * the Threading classes.
 * 
 * @author Florian Schnell
 */
class Tools
{
	/**
	 * Returns the database Connection
	 * that is being used to communicate
	 * between the threads.
	 * @return \ManiaLive\Database\SQLite\Connection
	 */
	static function getDb($mainPid = null)
	{
		if ($mainPid === null)
		{
			return Connection::getConnection('threading_' . getmypid(), null, null, null, 'SQLite');
		}
		else
		{
			return Connection::getConnection('threading_' . $mainPid, null, null, null, 'SQLite');
		}
	}
	
	/**
	 * This will setup the table
	 * structure that is needed for
	 * Thread communication.
	 */
	static function setupDb()
	{
		$db = self::getDb();
		
		// create table threads ...
		if (!$db->tableExists('threads'))
		{
			$db->execute('CREATE TABLE threads
				(
					thread_id INTEGER NOT NULL,
					proc_id INTEGER NULL,
					last_beat TEXT NULL,
					busy BOOL NULL,
					state TEXT NULL,
					PRIMARY KEY (thread_id)
				)');
		}
		
		// create table cmd ...
		if (!$db->tableExists('cmd'))
		{
			$db->execute('CREATE TABLE cmd
				(
					cmd_id INTEGER NOT NULL,
					thread_id INTEGER NOT NULL,
					proc_id INTEGER NULL,
					cmd TEXT NULL,
					param TEXT NULL,
					result TEXT NULL,
					done BOOL NULL,
					datestamp TEXT NULL,
					PRIMARY KEY (cmd_id)
				)');
		}
	}
}