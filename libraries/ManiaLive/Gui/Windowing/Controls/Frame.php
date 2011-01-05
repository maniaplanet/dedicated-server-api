<?php

namespace ManiaLive\Gui\Windowing\Controls;

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