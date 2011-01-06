<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\Database\SQLite;

use ManiaLive\Database\NotSupportedException;
use ManiaLive\Database\QueryException;
use ManiaLive\Database\DisconnectionException;
use ManiaLive\Database\NotConnectedException;
use ManiaLive\Database\Exception;
use ManiaLive\Database\ConnectionException;

/**
 * SQLite Database Connection implementation.
 * @author Florian Schnell
 */
class Connection extends \ManiaLive\Database\Connection
{
	protected $connection;
	protected $filename;

	function __construct($host, $username, $password, $database, $port)
	{
		// create the data subfolder in root directory ...
		$datapath = APP_ROOT . 'data';
		if (!file_exists($datapath))
		mkdir($datapath);

		// move the database file in data subfolder ...
		$this->filename = $datapath.'/'.$host . '.db';

		// create new connection ...
		$this->connection = sqlite_open($this->filename);

		// check
		if (!$this->connection)
		{
			throw new ConnectionException;
		}

		// don't need to set utf8 ...
	}

	function getHandle()
	{
		return $this->connection;
	}
	
	function setCharset($charset)
	{
		throw new NotSupportedException;
	}

	function isConnected()
	{
		return ($this->connection != null && $this->connection != false);
	}

	function getDatabase()
	{
		return $this->filename;
	}

	function affectedRows()
	{
		return sqlite_changes($this->connection);
	}

	function insertID()
	{
		return sqlite_last_insert_rowid($this->connection);
	}

	function query($query)
	{
		if (!$this->isConnected())
		{
			throw new NotConnectedException;
		}

		Connection::startMeasuring($this);
		if(func_num_args() > 1)
		{
			$query = call_user_func_array('sprintf', func_get_args());
		}
		$result = sqlite_query($this->connection, $query);
		Connection::endMeasuring($this);

		if (!$result)
		{
			$errno = sqlite_last_error($this->connection);
			$errstr = sqlite_error_string($errno);
			throw new QueryException($errstr, $errno);
		}

		return new RecordSet($result);
	}

	function execute($query)
	{
		Connection::startMeasuring($this);
		if(func_num_args() > 1)
		{
			$query = call_user_func_array('sprintf', func_get_args());
		}
		$result = sqlite_exec($this->connection, $query);
		Connection::endMeasuring($this);

		if ($result === false)
		{
			$errno = sqlite_last_error($this->connection);
			$errstr = sqlite_error_string($errno);
			throw new QueryException($errstr, $errno);
		}
	}

	function disconnect()
	{
		if (!sqlite_close($this->connection))
		{
			throw new DisconnectionException;
		}
	}

	function quote($string)
	{
		return sqlite_escape_string($string);
	}

	function select($database)
	{
		throw new NotSupportedException;
	}

	function tableExists($table_name)
	{
		$table = $this->query("SELECT name FROM sqlite_master WHERE name='" . $table_name . "' AND type='table'");
		return ($table->recordCount() > 0);
	}
}

?>
