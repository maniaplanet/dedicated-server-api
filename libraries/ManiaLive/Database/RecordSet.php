<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\Database;

interface RecordSet
{
	const FETCH_ASSOC = MYSQL_ASSOC;
	const FETCH_NUM = MYSQL_NUM;
	const FETCH_BOTH = MYSQL_BOTH;
	
	function __construct($result);
	
	function fetchRow();
	
	function fetchAssoc();
	
	function fetchArray();
	
	function fetchStdObject();
	
	function fetchObject($className, array $params=array() );
	
	function recordCount();
	
	function recordAvailable();
	
	function fetchScalar();
}

?>