<?php
/**
 * ManiaLib - Lightweight PHP framework for Manialinks
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLib\Gui\Layouts;

/**
 * Flow layout
 * Column-like, items fill vertically the current column then the next one on the right etc.
 */
class VerticalFlow extends AbstractLayout
{
	/**#@+
	 * @ignore
	 */
	protected $maxWidth = 0;
	protected $currentColumnElementCount = 0;
	/**#@-*/

	/**
	 * @ignore
	 */
	function preFilter(\ManiaLib\Gui\Component $item)
	{
		// add minimal number to avoid floating error
		$availableHeight = $this->sizeY + $this->yIndex - $this->borderHeight + 0.1;

		// If end of the line is reached
		if($availableHeight < $item->getRealSizeY() & $this->currentColumnElementCount > 0)
		{
			$this->xIndex += $this->maxWidth + $this->marginWidth;
			$this->yIndex = $this->borderHeight;
			$this->currentColumnElementCount = 0;
			$this->maxWidth = 0;
		}

	}

	/**
	 * @ignore
	 */
	function postFilter(\ManiaLib\Gui\Component $item)
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