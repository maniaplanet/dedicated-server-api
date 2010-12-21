<?php

namespace ManiaLive\Gui\Windowing;

/**
 * This will provide automatic call of the onIsAdded method
 * when stored in an object of type Container.
 * 
 * @author Florian Schnell
 * @copyright 2010 NADEO
 */
interface Containable
{
	/**
	 * This method is invoked when adding an object of this type
	 * to a Container class object.
	 * @param Container $target Reference to the target Container.
	 */
	function onIsAdded(Container $target);
}

?>