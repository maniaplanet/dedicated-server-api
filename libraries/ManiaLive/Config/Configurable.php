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

namespace ManiaLive\Config;

abstract class Configurable
{
	/**
	 * This is called once by the config loader to check the values are set as they should
	 * @see ManiaLib_Config_Configurable::checkExists()
	 * @see ManiaLib_Config_Configurable::setDefault()
	 */
	protected function validate() {}
	
	final function __construct()
	{
		$reflect = new \ReflectionClass($this);
		$props = $reflect->getProperties();
		foreach($props as $prop)
		{
			if(!$prop->isStatic() && $prop->isPublic())
			{
				$comment = $prop->getDocComment();
				$matches = null;
				preg_match('/@var (\\\[[:alpha:][:alnum:]-_\\\]+)/', $comment, $matches);
				if(isset($matches[1]))
				{
					$class = $matches[1];
					if(class_exists($class))
					{
						if($class instanceof Configurable);
						{
							$key = $prop->getName();
							$this->$key = new $class;
						}
					}
				}
			}
		}
	}

	/**
	 * Check if a property is not null/empty, usefull for validation
	 * @throws Exception
	 */
	final protected function checkExists($property)
	{
		if(!$this->$property)
		{
			throw new Exception(get_class($this).'::'.$property.' must be defined');
		}
	}
	
	/**
	 * Define a property's default value if it is null/empty, usefull for validation
	 */
	final protected function setDefault($property, $value)
	{
		if(!$this->$property)
		{
			$this->$property = $value;
		}
	}
	
	/**
	 * Actually validates the config, and then all the nested configs
	 */
	final public function doValidate()
	{
		$this->validate();
		foreach($this as $key => $value)
		{
			if($value instanceof Configurable)
			{
				$value->doValidate();
			}
		}
	}
}

?>