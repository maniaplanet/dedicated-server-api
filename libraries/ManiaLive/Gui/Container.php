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

namespace ManiaLive\Gui;

use ManiaLib\Gui\Drawable;

/**
 * Contains objects. Those implementing the Containable interface will be informed about it.
 */
abstract class Container extends \ManiaLib\Gui\Component
{
	private $components = array();
	
	/**
	 * Adds a new Component to the Container.
	 * @param Drawable $component
	 */
	function addComponent(Drawable $component)
	{
		if($component instanceof Containable)
			$component->onIsAdded($this);
		$this->components[spl_object_hash($component)] = $component;
	}
	
	/**
	 * Retrieve a list of the components that are currently stored in the Container.
	 * @return array[Drawable]
	 */
	function getComponents()
	{
		return $this->components;
	}
	
	/**
	 * Count recursively how many basic elements are contained
	 * @return integer
	 */
	function countElements()
	{
		$count = 0;
		foreach($this->components as $component)
		{
			if($component instanceof Container)
				$count += $component->countComponents();
			else
				++$count;
		}
	}
	
	/**
	 * Removes a single component from the container.
	 * @param Drawable $component
	 */
	function removeComponent(Drawable $component)
	{
		$hash = spl_object_hash($component);
		
		if(isset($this->components[$hash]))
		{
			if($this->components[$hash] instanceof Containable)
				$this->components[$hash]->onIsRemoved($this);
			unset($this->components[$hash]);
		}
	}
	
	/**
	 * Removes all objects from the container.
	 */
	function clearComponents()
	{
		foreach($this->components as $component)
		{
			if($component instanceof Containable)
				$component->onIsRemoved($this);
		}
		$this->components = array();
	}
}

?>