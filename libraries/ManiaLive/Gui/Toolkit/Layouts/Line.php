<?php
/**
 * @author Maxime Raoust
 * @copyright 2009-2010 NADEO 
 */
namespace ManiaLive\Gui\Toolkit\Layouts;

use ManiaLive\Gui\Toolkit\Component;
use ManiaLive\Gui\Toolkit\Elements\Element;

/**
 * Line layout
 * Elements are added at the right of their predecessor
 */
class Line extends AbstractLayout
{
	function postFilter(Component $item)
	{
		$this->xIndex += $item->getRealSizeX() + $this->marginWidth;
	}
}

?>