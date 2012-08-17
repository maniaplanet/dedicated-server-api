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

abstract class RecordSet
{
	abstract function __construct($result);
	
	/**
	 * @deprecated use fetchSingleValue instead
	 */
	function fetchScalar()
	{
		return $this->fetchSingleValue();
	}
	
	function fetchSingleValue($default = 0)
	{
		$row = $this->fetchRow();
		return $row ? reset($row) : $default;
	}

	function fetchArrayOfSingleValues()
	{
		$array = array();
		while($row = $this->fetchRow())
		{
			$array[] = reset($row);
		}
		return $array;
	}
	
	/**
	 * Get a result row as an enumerated array
	 * Returns an numerical array of strings that corresponds to the fetched row
	 * @return array 
	 */
	abstract function fetchRow();

	function fetchArrayOfRow()
	{
		$array = array();
		while($row = $this->fetchRow())
		{
			$array[] = $row;
		}
		return $array;
	}
	
	/**
	 * Fetch a result row as an associative array
	 * Returns an associative array of strings that corresponds to the fetched row
	 * @return array
	 */
	abstract function fetchAssoc();

	function fetchArrayOfAssoc()
	{
		$array = array();
		while($row = $this->fetchAssoc())
		{
			$array[] = $row;
		}
		return $array;
	}
	
	/**
	 * Fetch a result row as an associative array, a numeric array, or both
	 * Returns an array that corresponds to the fetched row and moves the internal data pointer ahead.
	 * @return array
	 */
	abstract function fetchArray();
	
	/**
	 * Fetch a result row as an Object
	 * Returns an object with properties that correspond to the fetched row and moves the internal data pointer ahead.
	 * @return object
	 */
	abstract function fetchObject($className='\\stdClass', array $params=array());

	function fetchArrayOfObject($className='\\stdClass', array $params=array())
	{
		$array = array();
		while($row = $this->fetchObject($className, $params))
		{
			$array[] = $row;
		}
		return $array;
	}
	
	/**
	 * Give the number of rows in the ResultSet
	 * This command is only valid for statements like SELECT or SHOW
	 * @return int
	 */
	abstract function recordCount();
	
	abstract function recordAvailable();
}

?>