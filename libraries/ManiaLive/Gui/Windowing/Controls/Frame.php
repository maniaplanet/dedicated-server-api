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

namespace ManiaLive\Gui\Windowing\Controls;

/**
 * Frame element will move all its content
 * when position changes.
 * You can also apply a layout that is applied
 * to all its subcomponents.
 * 
 * @author Florian Schnell
 */
class Frame extends \ManiaLive\Gui\Windowing\Control
{
	function initializeComponents()
	{
		$this->posX = $this->getParam(0);
		$this->posY = $this->getParam(1);
		if (($parent = $this->getParam(2))
			&& $parent instanceof \ManiaLive\Gui\Windowing\Container)
		{
			$parent->addComponent($this);
		}
	}
	
	function beforeDraw() {}
	
	function afterDraw() {}
}

?>