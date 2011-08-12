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

namespace ManiaLib\Gui;

/**
 * Manialink main class
 */
abstract class Manialink
{
	public static $domDocument;
	public static $parentNodes;
	public static $parentLayouts;
	public static $linksEnabled = true;
	
	public static $langsURL;
	public static $imagesURL;
	public static $mediaURL;
	
	protected static $swapPosY = false;
	
	/**
	 * Loads the Manialink GUI toolkit. This should be called before doing
	 * anything with the toolkit.
	 * @param bool Whether you want to create the root "<manialink>" element in the XML
	 */
	final public static function load($root = true, $timeout=0, $version=1, $background = 1)
	{
		if(class_exists('\ManiaLib\Application\Config'))
		{
			$config = \ManiaLib\Application\Config::getInstance();
			self::$langsURL = $config->getLangsURL();
			self::$imagesURL = $config->getImagesURL();
			self::$mediaURL = $config->getMediaURL();
		}
		
		// Load the XML object
		self::$domDocument = new \DOMDocument('1.0', 'utf-8');
		self::$parentNodes = array();
		self::$parentLayouts = array();

		if($root)
		{
			$nodeManialink = self::$domDocument->createElement('manialink');
			$nodeManialink->setAttribute('version', $version);
			if(!$background)
			{
				$nodeManialink->setAttribute('background', 0);
			}
			self::$domDocument->appendChild($nodeManialink);
			self::$parentNodes[] = $nodeManialink;
				
			$nodeTimeout = self::$domDocument->createElement('timeout');
			$nodeManialink->appendChild($nodeTimeout);
			$nodeTimeout->nodeValue = $timeout;
		}
		else
		{
			$frame = self::$domDocument->createElement('frame');
			self::$domDocument->appendChild($frame);
			self::$parentNodes[] = $frame;
		}
	}

	/**
	 * Renders the Manialink
	 * @param boolean Wehther you want to return the XML instead of printing it
	 */
	final public static function render($return = false)
	{
		if($return)
		{
			return self::$domDocument->saveXML();
		}
		else
		{
			header('Content-Type: text/xml; charset=utf-8');
			echo self::$domDocument->saveXML();
		}
	}

	/**
	 * Creates a new Manialink frame, with an optionnal associated layout
	 *
	 * @param float X position
	 * @param float Y position
	 * @param float Z position
	 * @param float Scale (default is null or 1)
	 * @param \ManiaLib\Gui\Layouts\AbstractLayout The optionnal layout associated with the frame. If
	 * you pass a layout object, all the items inside the frame will be
	 * positionned using constraints defined by the layout
	 */
	final public static function beginFrame($x=0, $y=0, $z=0, $scale=null, \ManiaLib\Gui\Layouts\AbstractLayout $layout=null)
	{
		// Update parent layout
		$parentLayout = end(self::$parentLayouts);
		if($parentLayout instanceof \ManiaLib\Gui\Layouts\AbstractLayout)
		{
			// If we have a current layout, we have a container size to deal with
			if($layout instanceof \ManiaLib\Gui\Layouts\AbstractLayout)
			{
				$ui = new \ManiaLib\Gui\Elements\Spacer($layout->getSizeX(), $layout->getSizeY());
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
			if (self::$swapPosY)
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
	 * Redirects the user to the specified Manialink
	 */
	final public static function redirect($link, $render = true)
	{
		self::$domDocument = new \DOMDocument('1.0', 'utf-8');
		self::$parentNodes = array();
		self::$parentLayouts = array();

		$redirect = self::$domDocument->createElement('redirect');
		$redirect->appendChild(self::$domDocument->createTextNode($link));
		self::$domDocument->appendChild($redirect);
		self::$parentNodes[] = $redirect;

		if($render)
		{
			if(ob_get_contents())
			{
				ob_clean();
			}
			header('Content-Type: text/xml; charset=utf-8');
			echo self::$domDocument->saveXML();
			// TODO ManiaLib: should we exit here?
			exit;
		}
		else
		{
			return self::$domDocument->saveXML();
		}
	}

	/**
	 * Append some XML code to the document
	 * @param string Some XML code
	 */
	static function appendXML($XML)
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML($XML);
		$node = self::$domDocument->importNode($doc->firstChild, true);
		end(self::$parentNodes)->appendChild($node);
	}

	/**
	 * Disable all Manialinks, URLs and actions of GUIElement objects as long as it is disabled
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
	 * This method is mainly used by ManiaLive.
	 */
	final public static function setNormalPositioning()
	{
		self::$swapPosY = false;
	}
	
	/**
	 * Swapped Manialink behavior for the Y positioning of Elements.
	 * This will increase from top to bottom.
	 * This method is mainly used by ManiaLive.
	 */
	final public static function setSwappedPositioning()
	{
		self::$swapPosY = true;
	}
	
	/**
	 * Returns whether Y-Positioning is swapped for all
	 * Elements currently drawn.
	 * This method is mainly used by ManiaLive.
	 */
	final public static function isYSwapped()
	{
		return self::$swapPosY;
	}
	
}

?>