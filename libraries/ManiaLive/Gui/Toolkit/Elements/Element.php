<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 1709 $:
 * @author      $Author: svn $:
 * @date        $Date: 2011-01-07 14:06:13 +0100 (ven., 07 janv. 2011) $:
 */

namespace ManiaLive\Gui\Toolkit\Elements;

use ManiaLive\Gui\Toolkit\Layouts\AbstractLayout;
use ManiaLive\Gui\Toolkit\Manialink;
use ManiaLive\Gui\Toolkit\Component;
use ManiaLive\Gui\Toolkit\Drawable;

/**
 * Base class for creating GUI elements
 */
abstract class Element extends Component implements Drawable
{
	const USE_ABSOLUTE_URL = null;

	protected $style;
	protected $subStyle;
	protected $manialink;
	protected $url;
	protected $maniazone;
	protected $bgcolor;
	protected $addPlayerId;
	protected $action;
	protected $actionKey;
	protected $image;
	protected $imageFocus;
	protected $xmlTagName = 'xmltag'; // Redeclare this for each child
	protected $xml;

	/**
	 * Manialink element default constructor. It's common to specify the size of
	 * the element in the constructor.
	 *
	 * @param float Width of the element
	 * @param float Height of the element
	 */
	function __construct($sizeX = 20, $sizeY = 20)
	{
		$this->sizeX = $sizeX;
		$this->sizeY = $sizeY;
		$this->visible = true;
	}

	/**
	 * Sets the style of the element. See http://fish.stabb.de/styles/ of the
	 * manialink 'example' for more information on Manialink styles.
	 * @param string
	 */
	function setStyle($style)
	{
		$this->style = $style;
	}

	/**
	 * Sets the sub-style of the element. See http://fish.stabb.de/styles/ of
	 * the manialink 'example' for more information on Manialink styles.
	 * @param string
	 */
	function setSubStyle($substyle)
	{
		$this->subStyle = $substyle;
	}

	/**
	 * Sets the Manialink of the element. It works as a hyperlink.
	 * @param string Can be either a short Manialink or an URL pointing to a
	 * Manialink
	 */
	function setManialink($manialink)
	{
		$this->manialink = $manialink;
	}

	/**
	 * Sets the hyperlink of the element
	 * @param string An URL
	 */
	function setUrl($url)
	{
		$this->url = $url;
	}

	/**
	 * Sets the Maniazones link of the element
	 * @param string
	 */
	function setManiazone($maniazone)
	{
		$this->maniazone = $maniazone;
	}

	/**
	 * Adds the player information parameters ("playerlogin", "nickname",
	 * "path", "lang") to the URL when you click on the link
	 */
	function addPlayerId()
	{
		$this->addPlayerId = 1;
	}

	/**
	 * Sets the action of the element. For example, if you use the action "0" in
	 * the explorer, it closes the explorer when you click on the element.
	 * @param int
	 */
	function setAction($action)
	{
		$this->action = $action;
	}

	/**
	 * Sets the action key associated to the element. Only works on dedicated
	 * servers.
	 * @param int
	 */
	function setActionKey($actionKey)
	{
		$this->actionKey = $actionKey;
	}

	/**
	 * Sets the background color of the element using a 3-digit RGB hexadecimal
	 * value. For example, "fff" is white and "000" is black
	 * @param string 3-digit RGB hexadecimal value
	 */
	function setBgcolor($bgcolor)
	{
		$this->bgcolor = $bgcolor;
	}

	/**
	 * Applies an image to the element. If you don't specify the second
	 * parameter, it will look for the image in the path defined by the
	 * APP_IMAGE_DIR_URL constant
	 * @param string The image filename (or URL)
	 */
	function setImage($image)
	{
		$this->setStyle(null);
		$this->setSubStyle(null);
		$this->image = $image;
	}

	/**
	 * Applies an image to the highlighter state of the element. The second
	 * parameter works just like Element::setImage()
	 * @param string The image filename (or URL)
	 */
	function setImageFocus($imageFocus)
	{
		$this->imageFocus = $imageFocus;
	}

	/**
	 * Returns the style of the element
	 * @return string
	 */
	function getStyle()
	{
		return $this->style;
	}

	/**
	 * Returns the substyle of the element
	 * @return string
	 */
	function getSubStyle()
	{
		return $this->subStyle;
	}

	/**
	 * Returns the Manialink hyperlink of the element
	 * @return string
	 */
	function getManialink()
	{
		return $this->manialink;
	}

	/**
	 * Returns the Maniazones hyperlink of the element
	 * @return string
	 */
	function getManiazone()
	{
		return $this->maniazone;
	}

	/**
	 * Returns the hyperlink of the element
	 * @return string
	 */
	function getUrl()
	{
		return $this->url;
	}

	/**
	 * Returns the action associated to the element
	 * @return int
	 */
	function getAction()
	{
		return $this->action;
	}

	/**
	 * Returns the action key associated to the element
	 * @return int
	 */
	function getActionKey()
	{
		return $this->actionKey;
	}

	/**
	 * Returns whether the elements adds player information parameter to the URL
	 * when it's clicked
	 * @return boolean
	 */
	function getAddPlayerId()
	{
		return $this->addPlayerId;
	}

	/**
	 * Returns the background color of the element
	 * @return string 3-digit RGB hexadecimal value
	 */
	function getBgcolor()
	{
		return $this->bgcolor;
	}

	/**
	 * Returns the image placed in the element
	 * @return string The image URL
	 */
	function getImage()
	{
		return $this->image;
	}

	/**
	 * Returns the image placed in the element in its highlighted state
	 * @return string The image URL
	 */
	function getImageFocus()
	{
		return $this->imageFocus;
	}

	/**
	 * Imports links and actions from another Manialink element
	 * @param Element The source object
	 */
	function addLink(Element $object)
	{
		$this->manialink = $object->getManialink();
		$this->url = $object->getUrl();
		$this->maniazone = $object->getManiazone();
		$this->action = $object->getAction();
		$this->actionKey = $object->getActionKey();
		if($object->getAddPlayerId())
		{
			$this->addPlayerId = 1;
		}
	}

	/**
	 * Returns whether the object has a link or an action (either Manialink,
	 * Maniazones link, hyperlink or action)
	 * @return string
	 */
	function hasLink()
	{
		return $this->manialink || $this->url || $this->action || $this->maniazone;
	}
	
	/**
	 * Override this method in subclasses to perform some action before
	 * rendering the element
	 */
	protected function preFilter()
	{

	}

	/**
	 * Override this method in subclasses to perform some action after rendering
	 * the element
	 */
	protected function postFilter()
	{

	}

	/**
	 * Saves the object in the Manialink object stack for further rendering.
	 * Thanks to the use of Element::preFilter() and Element::
	 * postFilter(), you shouldn't have to override this method
	 */
	final function save()
	{
		if ($this->visible === false)
		{
			return;
		}
		
		// Optional pre filtering
		$this->preFilter();

		// Layout handling
		$layout = end(Manialink::$parentLayouts);
		if($layout instanceof AbstractLayout)
		{
			$layout->preFilter($this);
			$this->posX += $layout->xIndex;
			if (Manialink::isYSwapped())
			{
				$this->posY -= $layout->yIndex;
			}
			else
			{
				$this->posY += $layout->yIndex;
			}
			$this->posZ += $layout->zIndex;
		}

		// DOM element creation
		if($this->xmlTagName)
		{
			$this->xml = Manialink::$domDocument->createElement($this->xmlTagName);
			end(Manialink::$parentNodes)->appendChild($this->xml);

			// Add pos
			if($this->posX || $this->posY || $this->posZ)
			{
				if (Manialink::isYSwapped())
				{
					$this->xml->setAttribute('posn',
					$this->posX.' '.(-$this->posY).' '.$this->posZ);
				}
				else
				{
					$this->xml->setAttribute('posn',
					$this->posX.' '.$this->posY.' '.$this->posZ);
				}
			}

			// Add size
			if($this->sizeX || $this->sizeY)
			{
				$this->xml->setAttribute('sizen', $this->sizeX.' '.$this->sizeY);
			}

			// Add alignement
			if($this->halign !== null)
			{
				$this->xml->setAttribute('halign', $this->halign);
			}
			if($this->valign !== null)
			{
				$this->xml->setAttribute('valign', $this->valign);
			}
			if($this->scale !== null)
			{
				$this->xml->setAttribute('scale', $this->scale);
			}

			// Add styles
			if($this->style !== null)
			{
				$this->xml->setAttribute('style', $this->style);
			}
			if($this->subStyle !== null)
			{
				$this->xml->setAttribute('substyle', $this->subStyle);
			}
			if($this->bgcolor !== null)
			{
				$this->xml->setAttribute('bgcolor', $this->bgcolor);
			}

			// Add links
			if($this->addPlayerId !== null && Manialink::$linksEnabled)
			{
				$this->xml->setAttribute('addplayerid', $this->addPlayerId);
			}
			if($this->manialink !== null && Manialink::$linksEnabled)
			{
				$this->xml->setAttribute('manialink', $this->manialink);
			}
			if($this->url !== null && Manialink::$linksEnabled)
			{
				$this->xml->setAttribute('url', $this->url);
			}
			if($this->maniazone !== null && Manialink::$linksEnabled)
			{
				$this->xml->setAttribute('maniazone', $this->maniazone);
			}

			// Add action
			if($this->action !== null && Manialink::$linksEnabled)
			{
				$this->xml->setAttribute('action', $this->action);
			}
			if($this->actionKey !== null && Manialink::$linksEnabled)
			{
				$this->xml->setAttribute('actionkey', $this->actionKey);
			}

			// Add images
			if($this->image !== null)
			{
				$this->xml->setAttribute('image', $this->image);
			}
			if($this->imageFocus !== null)
			{
				$this->xml->setAttribute('imagefocus', $this->imageFocus);
			}
		}

		// Layout post filtering
		if($layout instanceof AbstractLayout)
		{
			$layout->postFilter($this);
		}

		// Post filtering
		$this->postFilter();
	}
}

?>