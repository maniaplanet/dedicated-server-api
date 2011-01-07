<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 1709 $:
 * @author      $Author: svn $:
 * @date        $Date: 2011-01-07 14:06:13 +0100 (ven., 07 janv. 2011) $:
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