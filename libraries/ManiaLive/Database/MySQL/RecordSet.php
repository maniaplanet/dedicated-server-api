<?php

namespace ManiaLive\Database\MySQL;

class RecordSet implements \ManiaLive\Database\RecordSet
{

	protected $result;
	
	function __construct($result)
	{
		$this->result = $result;
	}
	
	function fetchRow()
	{
		return mysql_fetch_row($this->result);
	}
	
	function fetchAssoc()
	{
		return mysql_fetch_assoc($this->result);
	}
	
	function fetchArray($resultType = self::FETCH_ASSOC)
	{
		return mysql_fetch_array($this->result, $resultType);
	}
	
	function fetchStdObject()
	{
		return mysql_fetch_object($this->result);
	}
	
	function fetchObject($className, array $params=array() )
	{
		if($params)
		{
			return mysql_fetch_object($this->result, $className, $params);
		}	
		else
		{
			return mysql_fetch_object($this->result, $className);
		}	
	}
	
	function fetchScalar()
	{
		$row = mysql_fetch_row($this->result);
		return $row[0];
	}
	
	function recordCount()
	{
		return mysql_num_rows($this->result);
	}
	
	function recordAvailable()
	{
		return mysql_num_rows($this->result) > 0;
	}
}

?>