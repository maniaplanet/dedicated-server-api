<?php
/**
 * @copyright NADEO (c) 2010
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