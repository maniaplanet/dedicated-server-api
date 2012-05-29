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
	const FETCH_ASSOC = SQLITE_ASSOC;
	const FETCH_NUM = SQLITE_NUM;
	const FETCH_BOTH = SQLITE_BOTH;
	
	protected $result;
	
	function __construct($result)
	{
		$this->result = $result;
	}
	
	function fetchScalar()
	{
		$row = $this->fetchRow();
		return $row[0];
	}
	
	function fetchRow()
	{
		return sqlite_fetch_array($this->result, self::FETCH_NUM);
	}
	
	function fetchAssoc()
	{
		return sqlite_fetch_array($this->result, self::FETCH_ASSOC);
	}
	
	function fetchArray($resultType = self::FETCH_ASSOC)
	{
		return sqlite_fetch_array($this->result, $resultType);
	}
	
	function fetchStdObject()
	{
		return sqlite_fetch_object($this->result);
	}
	
	function fetchObject($className, array $params=array())
	{
		return sqlite_fetch_object($this->result, $className, $params);
	}
	
	function recordCount()
	{
		return sqlite_num_rows($this->result);
	}
	
	function recordAvailable()
	{
		return sqlite_num_rows($this->result) > 0;
	}
}
?>