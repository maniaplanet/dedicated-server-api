<?php
/**
 * @author Maxime Raoust
 * @copyright 2009-2010 NADEO
 */

namespace ManiaLive\Gui\Toolkit;


use ManiaLive\Gui\Toolkit\Layouts\AbstractLayout;
use ManiaLive\Gui\Toolkit\Elements\Spacer;
/**
 * GUI Toolkit
 *
 * Manialink GUI Toolkit main class
 *
 */
abstract class Manialink
{
	public static $domDocument;
	public static $parentNodes;
	public static $parentLayouts;
	public static $linksEnabled = true;
	protected static $idManialink = null;
	protected static $swap_y_pos = false;
	
	final public static function beginFrame($x=0, $y=0, $z=0, $scale = null, AbstractLayout $layout=null)
	{
		// Update parent layout
		$parentLayout = end(self::$parentLayouts);
		if($parentLayout instanceof AbstractLayout)
		{
			// If we have a current layout, we have a container size to deal with
			if($layout instanceof AbstractLayout)
			{
				$ui = new Spacer($layout->getSizeX(), $layout->getSizeY());
				
				$ui->setPosition($x, $y, $z);

				$parentLayout->preFilter($ui);
				$x += $parentLayout->xIndex;
				$y += $parentLayout->yIndex;
				$z += $parentLayout->zIndex;
				$parentLayout->postFilter($ui);
			}
		}

		// Create DOM element
		$frame = self::$domDocument->createElement('frame');
		if($x || $y || $z)
		{
			if (self::isYSwapped())
				$frame->setAttribute('posn', $x.' '.(-$y).' '.$z);
			else
				$frame->setAttribute('posn', $x.' '.$y.' '.$z);
		}
		end(self::$parentNodes)->appendChild($frame);
		if($scale)
		{
			$frame->setAttribute('scale', $scale);
		}

		// Update stacks
		self::$parentNodes[] = $frame;
		self::$parentLayouts[] = $layout;
	}

	/**
	 * Closes the current Manialink frame
	 */
	final public static function endFrame()
	{
		if(!end(self::$parentNodes)->hasChildNodes())
		{
			end(self::$parentNodes)->nodeValue = ' ';
		}
		array_pop(self::$parentNodes);
		array_pop(self::$parentLayouts);
	}

	/**
	 * Add the given XML code to the document
	 */
	static function appendXML($XML)
	{
		$doc = new \DOMDocument();
		$doc->loadXML($XML);
		$node = self::$domDocument->importNode($doc->firstChild, true);
		end(self::$parentNodes)->appendChild($node);
	}

	/**
	 * Disable all Manialinks, URLs and actions of Element objects as long as
	 * it is disabled
	 */
	static function disableLinks()
	{
		self::$linksEnabled = false;
	}

	/**
	 * Enable links
	 */
	static function enableLinks()
	{
		self::$linksEnabled = true;
	}
	
	/**
	 * Normal Manialink behavior for the Y positioning of Elements.
	 * This will decrease Y coordinates from top to bottom.
	 */
	final public static function setNormalPositioning()
	{
		self::$swap_y_pos = false;
	}
	
	/**
	 * Swapped Manialink behavior for the Y positioning of Elements.
	 * This will increase from top to bottom.
	 */
	final public static function setSwappedPositioning()
	{
		self::$swap_y_pos = true;
	}
	
	/**
	 * Returns whether Y-Positioning is swapped for all
	 * Elements currently drawn.
	 */
	final public static function isYSwapped()
	{
		return self::$swap_y_pos;
	}
}

?>