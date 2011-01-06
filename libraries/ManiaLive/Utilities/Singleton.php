<?php
/**
 * @copyright NADEO (c) 2010
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