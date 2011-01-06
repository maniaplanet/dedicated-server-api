<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\Gui\Windowing;

/**
 * This will provide automatic call of the onIsAdded method
 * when stored in an object of type Container.
 * 
 * @author Florian Schnell
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