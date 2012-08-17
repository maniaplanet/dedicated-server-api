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

namespace ManiaLive\Database;

abstract class Connection
{
	private static $connections = array();
	private static $queryStartTime = array();
	private static $queriesCount = array();
	private static $queriesTotalTimes = array();
	private static $queriesAverageTimes = array();
	public $id;

	/**
	 * Factory method that will create and
	 * manage database connections.
	 * 
	 * @param string $host
	 * @param string $username
	 * @param string $password
	 * @param string $database
	 * @param string $type
	 * @param integer $port
	 * @return \ManiaLive\Database\Connection
	 * @throws NotSupportedException
	 */
	static function getConnection($host = 'localhost', $username = 'root', $password = '', $database = 'manialive', $type = 'MySQL', $port = null)
	{
		// create connection handle ...
		$id = md5($type.'|'.$host.'|'.$username.'|'.$password.'|'.$database.'|'.$port);

		// return connection already available ...
		if(array_key_exists($id, self::$connections))
		{
			return self::$connections[$id];
		}

		// create class name/path depending on the database type to create ...
		$className = '\\ManiaLive\\Database\\'.$type.'\\Connection';

		// check whether database class exists ...
		if(!class_exists($className))
		{
			throw new NotSupportedException('There is no connection class for the database type "'.$type.'" yet!');
		}

		// check whether the class is an extension of the abstract connection class ...
		if(!is_subclass_of($className, '\\ManiaLive\\Database\\Connection'))
		{
			throw new NotSupportedException('The database type "'.$type.'" does not support the connection interface!');
		}

		// create connection ...
		$connection = new $className($host, $username, $password, $database, $port, null);
		$connection->id = $id;

		// and insert into the storage ...
		self::$connections[$id] = $connection;
		self::$queriesCount[$id] = 0;
		self::$queriesTotalTimes[$id] = 0;
		self::$queriesAverageTimes[$id] = 0;

		return $connection;
	}

	abstract function __construct($host, $username, $password, $database, $port);

	/**
	 * Set the charset used to communicate with the database
	 * @param string $charset
	 */
	abstract function setCharset($charset);

	/**
	 * select the given database
	 * @param string $database
	 */
	abstract function select($database);

	/**
	 * Escape and add quotes around the given string
	 * @param string $string
	 * @return string
	 */
	abstract function quote($string);

	/**
	 * Execute a query and return the result
	 * @deprecated use execute instead
	 * @return \ManiaLive\Database\RecordSet
	 */
	final function query($query)
	{
		return call_user_func_array(array($this, 'execute'), func_get_args());
	}

	/**
	 * Execute a query and return the result
	 * @param string $query
	 * @return \ManiaLive\Database\RecordSet
	 */
	abstract function execute($query);

	/**
	 * Return the number of rows affected by the previous query
	 * @return int
	 */
	abstract function affectedRows();

	/**
	 * Return the id of the last insert query
	 * @return int
	 */
	abstract function insertID();

	/**
	 * Return true if the connection to the database is open, false in the other cases
	 * @return bool
	 */
	abstract function isConnected();

	/**
	 * Close the current connection to the database
	 */
	abstract function disconnect();

	/**
	 * Get the selected database
	 * @return string 
	 */
	abstract function getDatabase();

	/**
	 * Check if the given table exists
	 * @param string $table
	 * @return bool
	 */
	abstract function tableExists($table);

	/**
	 * Return the current Connection Link
	 * @return resource
	 */
	abstract function getHandle();

	static function startMeasuring(Connection $connection)
	{
		self::$queryStartTime[$connection->id] = microtime(true);
	}

	static function endMeasuring(Connection $connection)
	{
		++self::$queriesCount[$connection->id];
		self::$queriesTotalTimes[$connection->id] += microtime(true) - self::$queryStartTime[$connection->id];
		self::$queriesAverageTimes[$connection->id] = self::$queriesTotalTimes[$connection->id] / self::$queriesCount[$connection->id];
	}

	static function getMeasuredAverageTimes()
	{
		return self::$queriesAverageTimes;
	}
}

class ConnectionException extends \Exception {}
class DisconnectionException extends \Exception {}
class NotSupportedException extends \Exception {}
class NotConnectedException extends \Exception {}
class SelectionException extends \Exception {}
class QueryException extends \Exception {}

?>