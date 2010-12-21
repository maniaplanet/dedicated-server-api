<?php

namespace ManiaLive\Database;

abstract class Connection
{
	static $connections = array();
	static $time_start = array();
	static $time_avg = array();
	
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
	static function getConnection($host = APP_DATABASE_HOST,
								  $username = APP_DATABASE_USER,
								  $password = APP_DATABASE_PASSWORD,
								  $database = APP_DATABASE_NAME,
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
		$class_name = '\ManiaLive\Database\\' . $type . '\Connection';
		
		// check whether database class exists ...
		if (!class_exists($class_name))
		{
			throw new NotSupportedException('There is no connection class for the database type "' . $type . '" yet!');
		}
		
		// check whether the class is an extension of the abstract connection class ...
		if (!is_subclass_of($class_name, '\ManiaLive\Database\Connection'))
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
	
	abstract function setCharset($charset);
	
	abstract function select($database);
		
	abstract function quote($string);
	
	/**
	 * @return \ManiaLive\Database\RecordSet
	 */
	abstract function query($query);
	
	abstract function execute($query);
	
	abstract function affectedRows();
	
	abstract function insertID();
	
	abstract function isConnected();
	
	abstract function disconnect();
	
	abstract function getDatabase();
	
	abstract function tableExists($table);
	
	static function startMeasuring(Connection $con)
	{
		self::$time_start[$con->id] = microtime(true);
	}
	
	static function endMeasuring(Connection $con)
	{
		$duration = microtime(true) - self::$time_start[$con->id];
		if (!isset(self::$time_avg[$con->id]))
			self::$time_avg[$con->id] = $duration;
		else
		{
			self::$time_avg[$con->id] += $duration;
			self::$time_avg[$con->id] /= 2;
		}
	}
	
	static function getMeasuredAvgTimes()
	{
		return self::$time_avg;
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