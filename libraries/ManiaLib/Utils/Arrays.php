<?php 
/**
 * ManiaLib - Lightweight PHP framework for Manialinks
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLib\Utils;

/**
 * Array stuff
 */
abstract class Arrays
{
	/**
	 * Returns the specified array element if it exists, or the default value
	 */
	static function get($array, $key, $default = null)
	{
		return array_key_exists($key, $array) ? $array[$key] : $default;
	}
	
	/**
	 * Returns the specified element if it exists and is not null (==), or the 
	 * default value
	 */
	static function getNotNull($array, $key, $default = null)
	{
		if(array_key_exists($key, $array) && $array[$key])
		{
			return $array[$key];
		}
		return $default;
	}
	
	static function getProperty(array $array, $property, $default = null)
	{
		$values = array();
		foreach ($array as $key => $value) 
		{
			if(is_null($value->$property))
			{
				$values[$key] = $default;
			}
			else 
			{
				$values[$key] = $value->$property; 
			}
		}
		return $values;
	}
}

?>