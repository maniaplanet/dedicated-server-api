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
 * Text-like, items fill the current line then next line etc.
 */
class Flow extends AbstractLayout
{
	protected $maxHeight = 0;
	protected $currentLineElementCount = 0;

	function preFilter(Component $item)
	{
		$availableWidth = $this->sizeX - $this->xIndex - $this->borderWidth;

		// If end of the line is reached
		if($availableWidth+0.1 < $item->getRealSizeX() & $this->currentLineElementCount > 0)
		{
			$this->yIndex -= $this->maxHeight + $this->marginHeight;
			$this->xIndex = $this->borderWidth;
			$this->currentLineElementCount = 0;
			$this->maxHeight = 0;
		}

	}

	function postFilter(Component $item)
	{
		$this->xIndex += $item->getRealSizeX() + $this->marginWidth;
		if(!$this->maxHeight || $item->getRealSizeX() > $this->maxHeight)
		{
			$this->maxHeight = $item->getRealSizeY();
		}
		$this->currentLineElementCount++;
	}
}

?>