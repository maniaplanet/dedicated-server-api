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

namespace ManiaLive\Utilities;

abstract class Singleton
{
	protected static $instances = array();
		
	static function getInstance()
	{
		$class = get_called_class();
		if(!isset(static::$instances[$class]))
		{
			
			static::$instances[$class] = new $class();
		}
		return static::$instances[$class];
	}
	
	protected function __construct() {}
	
	final protected function __clone() {} 
}

?>