<?php

namespace ManiaLive\Gui\Toolkit;

/**
 * Can be drawn onto the Screen.
 * 
 * @author Florian Schnell
 * @copyright 2010 NADEO
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