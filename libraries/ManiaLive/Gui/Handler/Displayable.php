<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\Gui\Handler;

interface Displayable
{
	/**
	 * This function needs to be overwritten with code to
	 * actually draw the window and its contents.
	 */
	function display($login);
	
	/**
	 * Stuff to do when a window is being hidden.
	 */
	function hide($login);
	
	/**
	 * Position on the screen.
	 */
	function getPosX();
	function getPosY();
	function getPosZ();
	
	/**
	 * Returns the window's ID.
	 * this actually is not needed, but kept for compability
	 */
	function getId();
}
?>