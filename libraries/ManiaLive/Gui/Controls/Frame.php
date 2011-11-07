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

namespace ManiaLive\Gui\Controls;

/**
 * Frame element will move all its content
 * when position changes.
 * You can also apply a layout that is applied
 * to all its subcomponents.
 * 
 * @author Florian Schnell
 */
class Frame extends \ManiaLive\Gui\Control
{
	function __construct($posX=0, $posY=0, $layout=null)
	{
		$this->posX = $posX;
		$this->posY = $posY;
		$this->layout = $layout;
	}
}

?>