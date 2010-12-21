<?php
/**
 * @author Maxime Raoust
 * @copyright 2009-2010 NADEO 
 */
namespace ManiaLive\Gui\Toolkit\Layouts;

use ManiaLive\Gui\Toolkit\Component;
use ManiaLive\Gui\Toolkit\Elements\Element;

/**
 * Column layout
 * Elements are added below their predecessor
 */
class Column extends AbstractLayout
{
	const DIRECTION_DOWN = -1;
	const DIRECTION_UP = 1;
	
	protected $direction = -1;
	
	function setDirection($direction)
	{
		$this->direction = $direction;
	}
	
	function preFilter(Component $item)
	{
		if($this->direction == self::DIRECTION_UP)
		{
			$this->yIndex += $item->getRealSizeY() + $this->marginHeight;
		}
	}
	
	function postFilter(Component $item)
	{
		if($this->direction == self::DIRECTION_DOWN)
		{
			$this->yIndex -= $item->getRealSizeY() + $this->marginHeight;
		}
	}
}

?>