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

interface RecordSet
{
	function __construct($result);
	
	/**
	 * Get a result row as an enumerated array
	 * Returns an numerical array of strings that corresponds to the fetched row
	 * @return array 
	 */
	function fetchRow();
	
	/**
	 * Fetch a result row as an associative array
	 * Returns an associative array of strings that corresponds to the fetched row
	 * @return array
	 */
	function fetchAssoc();
	
	/**
	 * Fetch a result row as an associative array, a numeric array, or both
	 * Returns an array that corresponds to the fetched row and moves the internal data pointer ahead.
	 * @return array
	 */
	function fetchArray();
	
	/**
	 * Fetch a result row as a StdClass object
	 * Returns an object with properties that correspond to the fetched row and moves the internal data pointer ahead.
	 * @return stdClass
	 */
	function fetchStdObject();
	
	/**
	 * Fetch a result row as an Object
	 * Returns an object with properties that correspond to the fetched row and moves the internal data pointer ahead.
	 * @return object
	 */
	function fetchObject($className, array $params=array() );
	
	/**
	 * Give the number of rows in the ResultSet
	 * This command is only valid for statements like SELECT or SHOW
	 * @return int
	 */
	function recordCount();
	
	function recordAvailable();
	
	function fetchScalar();
}

?>