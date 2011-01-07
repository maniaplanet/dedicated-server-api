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

use ManiaLive\Gui\Toolkit\Drawable;
use ManiaLive\Gui\Toolkit\Component;

/**
 * Contains objects implementing the Containable interface.
 * Ensures that each object that is added will be informed about it.
 * 
 * @author Florian Schnell
 */
abstract class Container extends Component
{
	protected $components = array();
	
	/**
	 * Removes all objects from the container.
	 */
	function clearComponents()
	{
		$this->components = array();
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
}

?>