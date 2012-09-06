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

namespace ManiaLive\Database\MySQL;

class RecordSet extends \ManiaLive\Database\RecordSet
{
	const FETCH_ASSOC = MYSQL_ASSOC;
	const FETCH_NUM = MYSQL_NUM;
	const FETCH_BOTH = MYSQL_BOTH;
	
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
	
	/**
	 * @deprecated
	 */
	function fetchStdObject()
	{
		return $this->fetchObject(null);
	}
	
	function fetchObject($className='\\stdClass', array $params=array())
	{
		if($className)
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
		else
		{
			return mysql_fetch_object($this->result);
		}
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