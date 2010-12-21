<?php
/**
 * @author Maxime Raoust
 * @copyright 2009-2010 NADEO 
 */
namespace ManiaLive\Gui\Toolkit\Layouts;

use ManiaLive\Gui\Toolkit\Component;
use ManiaLive\Gui\Toolkit\Elements\Element;

/**
 * Flow layout
 * Column-like, items fill vertically the current column then the next one on the right etc.
 */
class VerticalFlow extends AbstractLayout
{
	protected $maxWidth = 0;
	protected $currentColumnElementCount = 0;

	function preFilter(Component $item)
	{
		$availableHeight = $this->sizeY + $this->yIndex - $this->borderHeight;

		// If end of the line is reached
		if($availableHeight < $item->getRealSizeY() & $this->currentColumnElementCount > 0)
		{
			$this->xIndex += $this->maxWidth + $this->marginWidth;
			$this->yIndex = $this->borderHeight;
			$this->currentColumnElementCount = 0;
			$this->maxWidth = 0;
		}

	}

	function postFilter(Component $item)
	{
		$this->yIndex -= $item->getRealSizeY() + $this->marginHeight;
		if(!$this->maxWidth || $item->getRealSizeX() > $this->maxWidth)
		{
			$this->maxWidth = $item->getRealSizeX();
		}
		$this->currentColumnElementCount++;
	}
}

?>