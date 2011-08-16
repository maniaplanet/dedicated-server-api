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
	static $connections = array();
	static $timeStart = array();
	static $timeAvg = array();
	
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
	static function getConnection($host = 'localhost',
								  $username = 'root',
								  $password = '',
								  $database = 'manialive',
								  $type = 'MySQL',
								  $port = null)
	{
		// create connection handle ...
		$id = md5($type.'|'.$host.'|'.$username.'|'.$password.'|'.$database.'|'.$port);
		
		// return connection already available ...
		if (array_key_exists($id, self::$connections))
		{
			return self::$connections[$id];
		}
		
		// create class name/path depending on the database type to create ...
		$class_name = '\\ManiaLive\\Database\\' . $type . '\\Connection';
		
		// check whether database class exists ...
		if (!class_exists($class_name))
		{
			throw new NotSupportedException('There is no connection class for the database type "' . $type . '" yet!');
		}
		
		// check whether the class is an extension of the abstract connection class ...
		if (!is_subclass_of($class_name, '\\ManiaLive\\Database\\Connection'))
		{
			throw new NotSupportedException('The database type "' . $type . '" does not support the connection interface!');
		}
		
		// create connection ...
		$connection = new $class_name($host, $username, $password, $database, $port, null);
		$connection->id = $id;
		
		// and insert into the storage ...
		self::$connections[$id] = $connection;
		
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
	 * @return \ManiaLive\Database\RecordSet
	 */
	abstract function query($query);
	
	/**
	 * Execute a query but it does not return any result
	 * @param string $query
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
	
	static function startMeasuring(Connection $con)
	{
		self::$timeStart[$con->id] = microtime(true);
	}
	
	static function endMeasuring(Connection $con)
	{
		$duration = microtime(true) - self::$timeStart[$con->id];
		if (!isset(self::$timeAvg[$con->id]))
		{
			self::$timeAvg[$con->id] = $duration;
		}
		else
		{
			self::$timeAvg[$con->id] += $duration;
			self::$timeAvg[$con->id] /= 2;
		}
	}
	
	static function getMeasuredAvgTimes()
	{
		return self::$timeAvg;
	}
}

class Exception extends \Exception {}

class ConnectionException extends Exception {}

class DisconnectionException extends Exception {}

class NotSupportedException extends Exception {}

class NotConnectedException extends Exception {}

class SelectionException extends Exception {}

class QueryException extends Exception {}
?>