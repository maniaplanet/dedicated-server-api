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
 * Line layout
 * Elements are added at the right of their predecessor
 */
class Line extends AbstractLayout
{
	/**
	 * @ignore
	 */
	function postFilter(\ManiaLib\Gui\Component $item)
	{
		$this->xIndex += $item->getRealSizeX() + $this->marginWidth;
	}
}

?>