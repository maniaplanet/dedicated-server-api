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

namespace ManiaLive\Gui\Windowing;

use ManiaLib\Gui\Drawable;
use ManiaLib\Gui\Component;

/**
 * Contains objects implementing the Containable interface.
 * Ensures that each object that is added will be informed about it.
 * 
 * @author Florian Schnell
 */
abstract class Container extends Component
{
	protected $components = array();
	protected $componentList = array();
	protected $componentsCount = 0;
	
	/**
	 * Removes all objects from the container.
	 */
	function clearComponents()
	{
		$temp = array();
		foreach ($this->components as &$component)
		{
			if ($component instanceof Containable)
			{
				$component->onIsRemoved($this);
				$temp[] = $component;
			}
		}
		$this->componentList = array();
		$this->components = array();
		return $temp;
	}
	
	/**
	 * Adds a new Component to the Container.
	 * @param ManiaLive\Gui\Toolkit\Drawable $component
	 */
	function addComponent(Drawable $component)
	{
		if ($component instanceof Containable)
		{
			$component->onIsAdded($this);
		}
		
		$this->components[] = $component;
		$this->componentList[spl_object_hash($component)] = $this->componentsCount++;
		
		$this->onComponentIsAdded($component);
	}
	
	/**
	 * Retrieve a list of the components that are corrently
	 * stored in the Container.
	 * @return array[Drawable]
	 */
	function getComponents()
	{
		return $this->components;
	}
	
	/**
	 * Removes a single component from the container.
	 * @param $component
	 */
	function removeComponent(Component $component)
	{
		$hash = spl_object_hash($component);
		
		// check if component exists
		if (!isset($this->componentList[$hash])) return;
		
		// remove control and index
		$index = $this->componentList[$hash];
		$this->components[$index]->onIsRemoved($this);
		unset($this->components[$index]);
		unset($this->componentList[$hash]);
	}
	
	/**
	 * This is called when a component is added
	 * to the container.
	 * @param Drawable $component
	 */
	protected function onComponentIsAdded(Drawable $component) {}
}

?>