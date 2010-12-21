<?php

namespace ManiaLive\Gui\Toolkit;

/**
 * The GuiComponent is a generic and abstract element that only contains
 * position, size and scale info.
 */
use ManiaLive\Gui\Windowing\Control;

abstract class Component
{
	protected $posX = 0;
	protected $posY = 0;
	protected $posZ = 0;
	protected $sizeX;
	protected $sizeY;
	protected $scale;
	protected $visible = true;
	protected $valign = null;
	protected $halign = null;
	
	/**
	 * Sets the X position of the element
	 * @param float
	 */
	function setPositionX($posX)
	{
		$this->posX = $posX;
		$this->onMove();
	}
	
	/**
	 * Sets the Y position of the element
	 * @param float
	 */
	function setPositionY($posY)
	{
		$this->posY = $posY;
		$this->onMove();
	}
	
	/**
	 * Sets the Z position of the element
	 * @param float
	 */
	function setPositionZ($posZ)
	{
		$this->posZ = $posZ;
		$this->onMove();
	}
	
	/**
	 * Sets the position of the element
	 * @param float
	 * @param float
	 * @param float
	 */
	function setPosition($posX = null, $posY = null, $posZ = null)
	{
		if ($posX !== null)
			$this->posX = $posX;
			
		if ($posY !== null)
			$this->posY = $posY;
			
		if ($posZ !== null)
			$this->posZ = $posZ;
		
		$this->onMove();
	}
	
	/**
	 * Sets the width of the element
	 * @param float
	 */
	function setSizeX($sizeX)
	{
		$this->sizeX = $sizeX;
		$this->onResize();
	}
	
	/**
	 * Sets the height of the element
	 * @param float
	 */
	function setSizeY($sizeY)
	{
		$this->sizeY = $sizeY;
		$this->onResize();
	}
	
	/**
	 * Sets the size of the element
	 * @param float
	 * @param float
	 */
	function setSize($sizeX, $sizeY)
	{
		$this->sizeX = $sizeX;
		$this->sizeY = $sizeY;
		$this->onResize();
	}
	
	/**
	 * Sets the scale factor of the element. 1=original size, 2=double size, 0.5
	 * =half size
	 * @param float
	 */
	function setScale($scale)
	{
		$this->scale = $scale;
	}
	
	/**
	 * Sets the vertical alignment of the element.
	 * @param string Vertical alignment can be either "top", "center" or
	 * "bottom"
	 */
	function setValign($valign)
	{
		$this->valign = $valign;
	}

	/**
	 * Sets the horizontal alignment of the element
	 * @param string Horizontal alignement can be eithe "left", "center" or
	 * "right"
	 */
	function setHalign($halign)
	{
		$this->halign = $halign;
	}

	/**
	 * Sets the alignment of the element
	 * @param string Horizontal alignement can be eithe "left", "center" or
	 * "right"
	 * @param string Vertical alignment can be either "top", "center" or
	 * "bottom"
	 */
	function setAlign($halign = null, $valign = null)
	{
		$this->setHalign($halign);
		$this->setValign($valign);
	}
	
	/**
	 * Sets the visibility of the Component.
	 * @param bool $visible If set to false the Component (and subcomponents) is not rendered.
	 */
	function setVisibility($visible)
	{
		$this->visible = $visible;
	}
	
	/**
	 * Returns the X position of the element
	 * @return float
	 */
	function getPosX()
	{
		return $this->posX;
	}
	
	/**
	 * Returns the Y position of the element
	 * @return float
	 */
	function getPosY()
	{
		return $this->posY;
	}
	
	/**
	 * Returns the Z position of the element
	 * @return float
	 */
	function getPosZ()
	{
		return $this->posZ;
	}
	
	/**
	 * Returns the width of the element
	 * @return float
	 */
	function getSizeX()
	{
		return $this->sizeX;
	}
	
	/**
	 * Returns the height of the element
	 * @return float
	 */
	function getSizeY()
	{
		return $this->sizeY;
	}
	
	/**
	 * Returns the width of the element with the
	 * applied scaling factor.
	 * @return float
	 */
	function getRealSizeX()
	{
		return $this->sizeX * ($this->scale ? $this->scale : 1);
	}
	
	/**
	 * Returns the height of the element with the
	 * applied scaling factor.
	 * @return float
	 */
	function getRealSizeY()
	{
		return $this->sizeY * ($this->scale ? $this->scale : 1);
	}
	
	/**
	 * Returns the scale of the element
	 * @return float
	 */
	function getScale()
	{
		return $this->scale;
	}
	
	/**
	 * Return the x-coordinate for the left border of the Component.
	 * @return float
	 */
	function getBorderLeft()
	{
		return $this->getPosX();
	}
	
	/**
	 * Return the x-coordinate for the right border of the Component.
	 * @return float
	 */
	function getBorderRight()
	{
		return $this->getPosX() + $this->getRealSizeX();
	}
	
	/**
	 * Return y-coordinate for the top border of the Component.
	 * @return float
	 */
	function getBorderTop()
	{
		return $this->getPosY();
	}
	
	/**
	 * Return y-coordinate for the bottom border of the Component.
	 * @return float
	 */
	function getBorderBottom()
	{
		return $this->getPosY() + $this->getRealSizeY();
	}
	
	/**
	 * @todo moved Halign and Valign into the Component class, shall it stay here?
	 */
	
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
	 * Is the Component rendered onto the screen or not?
	 * @return bool
	 */
	function isVisible()
	{
		return $this->visible;
	}
	
	/**
	 * Overwriteable functions.
	 */
	protected function onResize() {}
	
	protected function onMove() {}
}

?>