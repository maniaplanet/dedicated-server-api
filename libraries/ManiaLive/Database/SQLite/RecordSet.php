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

class RecordSet implements \ManiaLive\Database\RecordSet
{
	const FETCH_ASSOC = SQLITE3_ASSOC;
	const FETCH_NUM = SQLITE3_NUM;
	const FETCH_BOTH = SQLITE3_BOTH;
	
	protected $result = array();
	protected $recordCount;
	
	function __construct($result)
	{
		while( ($row = $result->fetchArray(self::FETCH_ASSOC)) )
			$this->result[] = $row;
		$result->finalize();
		
		$this->recordCount = count($this->result);
	}
	
	function fetchScalar()
	{
		$row = $this->fetchRow();
		return $row[0];
	}
	
	function fetchRow()
	{
		$row = array_shift($this->result);
		if($row)
			return array_values($row);
		return null;
	}
	
	function fetchAssoc()
	{
		return array_shift($this->result);
	}
	
	function fetchArray($resultType = self::FETCH_ASSOC)
	{
		switch($resultType)
		{
			case self::FETCH_ASSOC: return $this->fetchAssoc();
			case self::FETCH_NUM: return $this->fetchRow();
			case self::FETCH_NUM:
				$row = array_shift($this->result);
				if($row)
					return array_merge($row, array_values($row));
				return null;
		}
	}
	
	function fetchStdObject()
	{
		return $this->fetchObject(null);
	}
	
	function fetchObject($className, array $params=array())
	{
		$properties = $this->fetchAssoc();

		if(is_null($className))
		{
			$object = new stdClass();
		}
		else
		{
			// call to class' constructor must be done after filling the fields
			$object = unserialize(sprintf('O:%d:"%s":0:{}', strlen($className), $className));
		}

		$reflector = new \ReflectionObject($object);
		foreach($properties as $key => $value)
		{
			try
			{
				$attribute = $reflector->getProperty($key);
				$attribute->setAccessible(true);
				$attribute->setValue($object, $value);
			}
			catch(\ReflectionException $e)
			{
				$object->$key = $value;
			}
		}
		
		// calling constructor
		call_user_func_array(array($object, '__construct'), $params);
		
		return $object;
	}
	
	function recordCount()
	{
		return $this->recordCount;
	}
	
	function recordAvailable()
	{
		return $this->recordCount > 0;
	}
}
?>