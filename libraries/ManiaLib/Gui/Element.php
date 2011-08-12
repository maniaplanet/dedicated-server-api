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
 * Base class for creating GUI elements
 */
abstract class Element extends Component implements Drawable
{
	const USE_ABSOLUTE_URL = true;

	/*	 * #@+
	 * @ignore
	 */

	protected $style;
	protected $subStyle;
	protected $manialink;
	protected $manialinkId;
	protected $url;
	protected $urlId;
	protected $maniazone;
	protected $bgcolor;
	protected $addPlayerId;
	protected $action;
	protected $actionKey;
	protected $image;
	protected $imageid;
	protected $imageFocus;
	protected $imageFocusid;
	protected $xmlTagName = 'xmltag'; // Redeclare this for each child
	protected $xml;
	/*	 * #@- */
	/**
	 * Used by cards, all the elements in that array will be renderd before
	 * the post filter.
	 * @var array[\ManiaLib\Gui\Element]
	 */
	protected $cardElements = array();

	/*	 * #@+
	 * Will be used to set the frame containing all the cards elements relative
	 * to the current element.
	 */
	protected $cardElementsHalign = 'left';
	protected $cardElementsValign = 'top';
	protected $cardElementsPosX = 0;
	protected $cardElementsPosY = 0;
	protected $cardElementsPosZ = 1;
	/**
	 * @var \ManiaLib\Gui\Layouts\AbstractLayout
	 */
	protected $cardElementsLayout = null;
	/*	 * #@- */

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
	 * Sets the Manialink id of the element. It works as a hyperlink.
	 * @param string Can be either a short Manialink or an URL pointing to a
	 * Manialink
	 */
	function setManialinkId($manialinkId)
	{
		$this->manialinkId = $manialinkId;
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
	 * Sets the hyperlink id of the element
	 * @param string An URL
	 */
	function setUrlId($urlId)
	{
		$this->urlId = $urlId;
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
		// FIXME ManiaLib Reset style+substyle
		$this->bgcolor = $bgcolor;
	}

	/**
	 * Applies an image to the element
	 * @param string The image filename (or URL)
	 * @param bool Whether to prefix the filename with the default images dir URL
	 */
	function setImage($image, $absoluteUrl = false)
	{
		$this->setStyle(null);
		$this->setSubStyle(null);
		if(!$absoluteUrl)
		{
			$this->image = \ManiaLib\Gui\Manialink::$imagesURL.$image;
		}
		else
		{
			$this->image = $image;
		}
	}

	/**
	 * Set the image id of the element, used for internationalization
	 */
	function setImageid($imageid)
	{
		$this->setStyle(null);
		$this->setSubStyle(null);
		$this->imageid = $imageid;
	}

	/**
	 * Applies an image to the highlighter state of the element
	 * @param string The image filename (or URL)
	 * @param bool Whether to prefix the filename with the default images dir URL
	 */
	function setImageFocus($imageFocus, $absoluteUrl = false)
	{
		$this->setStyle(null);
		$this->setSubStyle(null);
		if(!$absoluteUrl)
		{
			$this->imageFocus = \ManiaLib\Gui\Manialink::$imagesURL.$imageFocus;
		}
		else
		{
			$this->imageFocus = $imageFocus;
		}
	}

	/**
	 * Set the image focus id of the element, used for internationalization
	 */
	function setImageFocusid($imageFocusid)
	{
		$this->setStyle(null);
		$this->setSubStyle(null);
		$this->imageFocusid = $imageFocusid;
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
	 * Returns the horizontal alignment of the element
	 * @return string
	 */
	function getHalign()
	{
		return $this->halign;
	}

	/**
	 * Returns the vertical alignment of the element
	 * @return string
	 */
	function getValign()
	{
		return $this->valign;
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
	 * Returns the Manialink hyperlink id of the element
	 * @return string
	 */
	function getManialinkId()
	{
		return $this->manialinkId;
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
	 * Returns the hyperlink id of the element
	 * @return string
	 */
	function getUrlId()
	{
		return $this->urlId;
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

	function getImageid()
	{
		return $this->imageid;
	}

	/**
	 * Returns the image placed in the element in its highlighted state
	 * @return string The image URL
	 */
	function getImageFocus()
	{
		return $this->imageFocus;
	}

	function getImageFocusid()
	{
		return $this->imageFocusid;
	}

	/**
	 * Imports links and actions from another Manialink element
	 * @param \ManiaLib\Gui\Element The source object
	 */
	function addLink(\ManiaLib\Gui\Element $object)
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

	function setCardElementPosition($posX=0, $posY=0, $posZ=0)
	{
		$this->cardElementsPosX = $posX;
		$this->cardElementsPosY = $posY;
		$this->cardElementsPosZ = $posZ;
	}

	protected function addCardElement(\ManiaLib\Gui\Element $element)
	{
		$this->cardElements[] = $element;
	}

	/**
	 * Override this method in subclasses to perform some action before
	 * rendering the element
	 * @ignore
	 */
	protected function preFilter()
	{
		
	}

	/**
	 * Override this method in subclasses to perform some action after rendering
	 * the element
	 * @ignore
	 */
	protected function postFilter()
	{
		
	}

	/**
	 * Saves the object in the Manialink object stack for further rendering.
	 * Thanks to the use of \ManiaLib\Gui\Element::preFilter() and \ManiaLib\Gui\Element::
	 * postFilter(), you shouldn't have to override this method
	 */
	final function save()
	{
		// this check is important for ManiaLive
		if($this->visible === false)
		{
			return;
		}

		// Optional pre filtering
		$this->preFilter();

		// Layout handling
		$layout = end(\ManiaLib\Gui\Manialink::$parentLayouts);
		if($layout instanceof \ManiaLib\Gui\Layouts\AbstractLayout)
		{
			$layout->preFilter($this);
			$this->posX += $layout->xIndex;

			// if we swap positioning in ManiaLive, then we
			// need to check that before positioning.
			if(Manialink::isYSwapped())
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
			$this->xml = \ManiaLib\Gui\Manialink::$domDocument->createElement($this->xmlTagName);
			end(\ManiaLib\Gui\Manialink::$parentNodes)->appendChild($this->xml);

			// Add pos
			if($this->posX || $this->posY || $this->posZ)
			{
				// ManiaLive check whether position is swapped.
				if(Manialink::isYSwapped())
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
				$this->xml->setAttribute('halign', $this->halign);
			if($this->valign !== null)
				$this->xml->setAttribute('valign', $this->valign);
			if($this->scale !== null)
				$this->xml->setAttribute('scale', $this->scale);

			// Add styles
			if($this->style !== null)
				$this->xml->setAttribute('style', $this->style);
			if($this->subStyle !== null)
				$this->xml->setAttribute('substyle', $this->subStyle);
			if($this->bgcolor !== null)
				$this->xml->setAttribute('bgcolor', $this->bgcolor);

			// Add links
			if($this->addPlayerId !== null && \ManiaLib\Gui\Manialink::$linksEnabled)
				$this->xml->setAttribute('addplayerid', $this->addPlayerId);
			if($this->manialink !== null && \ManiaLib\Gui\Manialink::$linksEnabled)
				$this->xml->setAttribute('manialink', $this->manialink);
			if($this->manialinkId !== null && \ManiaLib\Gui\Manialink::$linksEnabled)
				$this->xml->setAttribute('manialinkId', $this->manialinkId);
			if($this->url !== null && \ManiaLib\Gui\Manialink::$linksEnabled)
				$this->xml->setAttribute('url', $this->url);
			if($this->urlId !== null && \ManiaLib\Gui\Manialink::$linksEnabled)
				$this->xml->setAttribute('urlid', $this->urlId);
			if($this->maniazone !== null && \ManiaLib\Gui\Manialink::$linksEnabled)
				$this->xml->setAttribute('maniazone', $this->maniazone);

			// Add action
			if($this->action !== null && \ManiaLib\Gui\Manialink::$linksEnabled)
				$this->xml->setAttribute('action', $this->action);
			if($this->actionKey !== null)
				$this->xml->setAttribute('actionkey', $this->actionKey);

			// Add images
			if($this->image !== null)
				$this->xml->setAttribute('image', $this->image);
			if($this->imageid !== null)
				$this->xml->setAttribute('imageid', $this->imageid);
			if($this->imageFocus !== null)
				$this->xml->setAttribute('imagefocus', $this->imageFocus);
			if($this->imageFocusid !== null)
				$this->xml->setAttribute('imagefocusid', $this->imageFocusid);
		}

		// Layout post filtering
		if($layout instanceof \ManiaLib\Gui\Layouts\AbstractLayout)
		{
			$layout->postFilter($this);
		}

		// Card Elements
		if($this->cardElements)
		{
			// Algin the title and its bg at the top center of the main quad		
			$arr = \ManiaLib\Gui\Tools::getAlignedPos(
					$this, $this->cardElementsHalign, $this->cardElementsValign);
			$x = $arr["x"];
			$y = $arr["y"];


			// Draw them
			\ManiaLib\Gui\Manialink::beginFrame(
				$x + $this->cardElementsPosX, $y + $this->cardElementsPosY,
				$this->posZ + $this->cardElementsPosZ, $this->scale);
			\ManiaLib\Gui\Manialink::beginFrame(0, 0, 0, null, $this->cardElementsLayout);

			foreach($this->cardElements as $element)
			{
				$element->save();
			}

			\ManiaLib\Gui\Manialink::endFrame();
			\ManiaLib\Gui\Manialink::endFrame();
		}

		// Post filtering
		$this->postFilter();
	}

}

?>