<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\Gui\Toolkit;

/**
 * Can be drawn onto the Screen.
 */
interface Drawable
{
	/**
	 * This draws the object onto the screen.
	 * Contains the drawing process.
	 */
	function save();
}

?>