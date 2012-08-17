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

namespace ManiaLive\Database\SQLite;

use ManiaLive\Database\NotSupportedException;
use ManiaLive\Database\QueryException;
use ManiaLive\Database\DisconnectionException;
use ManiaLive\Database\NotConnectedException;
use ManiaLive\Database\Exception;
use ManiaLive\Database\ConnectionException;

/**
 * SQLite Database Connection implementation.
 */
class Connection extends \ManiaLive\Database\Connection
{
	/** @var \SQLite3 */
	protected $connection;
	protected $filename;

	function __construct($host, $username, $password, $database, $port)
	{
		$datapath = APP_ROOT.'data';
		if(!file_exists($datapath))
			mkdir($datapath);

		$this->filename = $datapath.'/'.$host.'.db';
		try
		{
			$this->connection = new \SQLite3($this->filename);
		}
		catch(\Exception $e)
		{
			$this->connection = null;
			throw new ConnectionException($e->getMessage());
		}
	}

	function getHandle()
	{
		return $this->connection;
	}
	
	function setCharset($charset)
	{
		throw new NotSupportedException();
	}

	function isConnected()
	{
		return $this->connection != null;
	}

	function getDatabase()
	{
		return $this->filename;
	}

	function affectedRows()
	{
		return $this->connection->changes();
	}

	function insertID()
	{
		return $this->connection->lastInsertRowID();
	}

	function execute($query)
	{
		if(!$this->isConnected())
		{
			throw new NotConnectedException;
		}
		
		Connection::startMeasuring($this);
		if(func_num_args() > 1)
		{
			$query = call_user_func_array('sprintf', func_get_args());
		}
		$result = $this->connection->query($query);
		Connection::endMeasuring($this);
		
		if(!$result)
		{
			throw new QueryException($this->connection->lastErrorMsg(), $this->connection->lastErrorCode());
		}
		if($result instanceof \SQLite3Result)
		{
			return new RecordSet($result);
		}
		return $result;
	}

	function disconnect()
	{
		if (!$this->connection->close())
		{
			throw new DisconnectionException;
		}
	}

	function quote($string)
	{
		return '\''.\SQLite3::escapeString($string).'\'';
	}

	function select($database)
	{
		throw new NotSupportedException();
	}

	function tableExists($tableName)
	{
		$table = $this->execute(
				'SELECT name FROM sqlite_master WHERE name=%s AND type=%s',
				$this->quote($tableName),
				$this->quote('table')
			);
		return $table->recordAvailable();
	}
}

?>
