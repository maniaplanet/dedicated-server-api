<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLive\Gui\Windowing;

use ManiaLib\Gui\Manialink;

/**
 * This class is instanciated each time a Window is being
 * displayed or updated on the screen.
 * 
 * @author Florian Schnell
 */
class WindowDisplayable extends \ManiaLive\Gui\Windowing\Control
	implements \ManiaLive\Gui\Handler\Displayable
{
	/**
	 * Reference to the window that is being displayed.
	 * @var ManiaLive\Gui\Windowing\Window
	 */
	public $window;
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Gui/Handler/ManiaLive\Gui\Handler.Displayable::display()
	 */
	function display($login)
	{
		// this view is out of date!
		if (!$this->window)
		{
			return;
		}
		
		// draw window and all subcomponents ...
		$this->save();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Gui/Handler/ManiaLive\Gui\Handler.Displayable::hide()
	 */
	function hide($login) {}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Gui/Windowing/ManiaLive\Gui\Windowing.Control::initializeComponents()
	 */
	function initializeComponents() {}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Gui/Windowing/ManiaLive\Gui\Windowing.Drawable::onDraw()
	 */
	function beforeDraw() {
		
		// if eg. a dialog has been drawn then we need to deactivate all links
		// in this window ...
		if ($this->window->getLinksDeactivated())
		{
			Manialink::disableLinks();
		}
		
		// invert y-axis for the drawing of this displayable ...
		if (!$this->window->getClassicPositioning())
		{
			Manialink::setSwappedPositioning();
		}
		
		$this->posZ = \ManiaLive\Gui\Windowing\Z_MIN;
		
		// search the windows below for the one with the highest z-index ...
		$below = $this->window->getWindowsBelow();
		$z_max = 0;
		$z = 0;
		if (!empty($below))
		{
			foreach ($below as $window)
			{
				$z = $window->getMaxZ();
				if ($z > $z_max) $z_max = $z;
			}
			if ($z_max > $this->posZ)
			{
				$this->posZ = $z_max;
			}
		}
		
		// an element to translate the page always needs to be on uppes level ...
		if ($this->window && $this->window->getHeaderElement() != null)
		{
			$this->window->getHeaderElement()->save();
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Gui/Windowing/ManiaLive\Gui\Windowing.Drawable::afterDraw()
	 */
	function afterDraw()
	{
		// if we have deactivated all links then activate them again ...
		Manialink::enableLinks();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Gui/Toolkit/ManiaLive\Gui\Toolkit.Component::getPosX()
	 */
	function getPosX()
	{
		return 0;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Gui/Toolkit/ManiaLive\Gui\Toolkit.Component::getPosY()
	 */
	function getPosY()
	{
		return 0;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Gui/Toolkit/ManiaLive\Gui\Toolkit.Component::getPosZ()
	 */
	function getPosZ()
	{
		return 0;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Gui/Handler/ManiaLive\Gui\Handler.Displayable::getId()
	 */
	function getId()
	{
		// this window is out of date
		return ($this->window === null ? null : $this->window->getId());
	}
	
	/**
	 * @return integer The highest z-index that has been used during the process of drawing.
	 */
	function getMaxZ()
	{
		return $this->zCur + $this->posZ;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Gui/Windowing/ManiaLive\Gui\Windowing.Control::destroy()
	 */
	function destroy()
	{
		$this->window = null;
		$this->parent = null;
		$this->components = null;
		$this->layout = null;
		$this->params = null;
		$this->componentList = null;
	}
}

?>