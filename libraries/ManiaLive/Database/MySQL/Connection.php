<?php

namespace ManiaLive\Database\MySQL;

use ManiaLive\Database\SelectionException;
use ManiaLive\Database\NotSupportedException;
use ManiaLive\Database\QueryException;
use ManiaLive\Database\DisconnectionException;
use ManiaLive\Database\NotConnectedException;
use ManiaLive\Database\Exception;
use ManiaLive\Database\ConnectionException;

class Connection extends \ManiaLive\Database\Connection
{
	protected $connection;
	protected $host;
	protected $user;
	protected $password;
	protected $database;
	
	function __construct($host, $username, $password, $database, $port)
	{
		// Init
		$this->host = $host;
		$this->user = $username;
		$this->password = $password;
		$this->clientFlags = 0;
		$this->referenceCount = 0;
		
		// Connection
		try
		{
			$this->connection = mysql_connect(
				$this->host,
				$this->user,
				$this->password
			);
		}
		catch(\ErrorException $err)
		{
			throw new ConnectionException($err->getMessage(), $err->getCode());
		}
				
		// Success ?
		if(!$this->connection)
		{
			throw new ConnectionException;
		}
		
		$this->select($database);	
		
		// Default Charset : UTF8
		self::setCharset('utf8');
	}
	
	function setCharset($charset)
	{
		if(function_exists('mysql_set_charset'))
		{
			if(!mysql_set_charset($charset, $this->connection))
			{
				throw new Exception;
			}
		}
		else
		{
			$charset = $this->quote($charset);
			$this->execute('SET NAMES '.$charset);
		}
	}
	
	function select($database)
	{
		if($database != $this->database)
		{
			$this->database = $database;
			if(!mysql_select_db($this->database, $this->connection))
			{
				throw new SelectionException(mysql_error($this->connection), mysql_errno($this->connection));
			}
		}
	}
		
	function quote($string)
	{
		return '\''.mysql_real_escape_string($string, $this->connection).'\'';
	}
	
	/**
	 * @param string The query
	 * @return DatabaseRecordSet
	 */
	function query($query)
	{
		Connection::startMeasuring($this);
		$result = mysql_query($query, $this->connection);
		Connection::endMeasuring($this);
		
		if(!$result)
		{
			throw new QueryException(mysql_error($this->connection), mysql_errno($this->connection));
		}
		return new RecordSet($result);
	}
	
	function execute($query)
	{
		Connection::startMeasuring($this);
		$result = mysql_unbuffered_query($query);
		Connection::endMeasuring($this);
		
		if (!$result)
		{
			throw new QueryException(mysql_error($this->connection), mysql_errno($this->connection));
		}
	}
	
	function affectedRows()
	{
		return mysql_affected_rows($this->connection);
	}
	
	function insertID()
	{
		return mysql_insert_id($this->connection);
	}
	
	function isConnected()
	{
		return (!$this->connection); 
	}
	
	function disconnect()
	{
		if(!mysql_close($this->connection))
		{
			throw new DisconnectionException;
		}
	}
	
	function getDatabase()
	{
		return $this->database;
	}
	
	function tableExists($table_name)
	{
		$table = $this->query("SHOW TABLES LIKE '".$table_name."'");
		return ($table->recordCount() > 0);
	}
}
?>