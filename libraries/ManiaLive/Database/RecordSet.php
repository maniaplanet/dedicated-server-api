<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 1709 $:
 * @author      $Author: svn $:
 * @date        $Date: 2011-01-07 14:06:13 +0100 (ven., 07 janv. 2011) $:
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