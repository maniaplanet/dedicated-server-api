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

namespace ManiaLive\Gui;

use ManiaLib\Gui\Drawable;
use ManiaLib\Gui\Manialink;
use ManiaLib\Gui\Layouts\AbstractLayout;

/**
 * Control class, use this to compose existing Elements.
 * You can also add Controls that already exist to attach Elements or to extend functionality.
 */
abstract class Control extends Container implements Drawable, Containable
{
	protected $layout = null;
	
	private $parents = array();
	private $linksDisabled = false;
	
	function disableLinks()
	{
		$this->linksDisabled = true;
	}
	
	function enableLinks()
	{
		$this->linksDisabled = false;
	}
	
	function areLinksDisabled()
	{
		return $this->linksDisabled;
	}
	
	/**
	 * Get current Layout from Control.
	 * @return AbstractLayout
	 */
	function getLayout()
	{
		return $this->layout;
	}
	
	/**
	 * Apply a Layout onto all subcontrols.
	 * @param AbstractLayout $layout
	 */
	function setLayout(AbstractLayout $layout)
	{
		$this->layout = $layout;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Gui/ManiaLive\Gui.Containable::onIsAdded()
	 */
	function onIsAdded(Container $target)
	{
		$this->parents[spl_object_hash($target)] = $target;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Gui/ManiaLive\Gui.Containable::onIsRemoved()
	 */
	function onIsRemoved(Container $target)
	{
		unset($this->parents[spl_object_hash($target)]);
	}
	
	/**
	 * Force a redraw of this component on the screens it is currently showed.
	 */
	final function redraw()
	{
		foreach($this->parents as $parent)
			$parent->redraw();
	}

	/**
	 * Override this method in subclasses to perform some action before rendering the element
	 * @ignore
	 */
	protected function onDraw() {}
	
	/**
	 * Renders the Control and all its Subelements/Subcontrols.
	 * Sets all Z-Indexes accordingly to the order of the items.
	 */
	final function save()
	{
		if(!$this->visible)
			return;
		
		$posX = $this->posX;
		$posY = $this->posY;
		
		// apply any layout to the underlying elements ...
		$layout = end(Manialink::$parentLayouts);
		if($layout instanceof AbstractLayout)
		{
			$layout->preFilter($this);
			
			$posX += $layout->xIndex;
			$posY += $layout->yIndex;
		}
		$this->onDraw();
		
		// horizontal alignment ...
		if($this->halign == 'right')
			$posX -= $this->getRealSizeX();
		else if($this->halign == 'center')
			$posX -= $this->getRealSizeX() / 2;
			
		// vertical alignment ...
		if($this->valign == 'bottom')
			$posY += $this->getRealSizeY();
		else if($this->valign == 'center')
			$posY += $this->getRealSizeY() / 2;
		
		// layout cloning, because manialib is used to erase objects after usage. 2 frames because of ManiaLib
		// (someday something better should be done about this)
		Manialink::beginFrame($posX, $posY, $this->posZ, $this->scale);
		if($this->id !== null)                  
			Manialink::setFrameId($this->id);
		if($this->layout)
			Manialink::beginFrame(0, 0, 0, null, $this->layout ? clone $this->layout : null);
		if($this->linksDisabled)
			Manialink::disableLinks();
		
		// render each element contained by the control and set z values ...
		$zCur = 0;
		foreach($this->getComponents() as $component)
		{
			$component->setPosZ($zCur);
			if($component instanceof Control)
				$zCur += $component->save();
			else
			{
				// layouts are modifying position so we need to set it back
				if($this->layout)
				{
					$oldX = $component->getPosX();
					$oldY = $component->getPosY();
					$component->save();
					$component->setPosition($oldX, $oldY);
				}
				else
					$component->save();
				$zCur += Window::Z_OFFSET;
			}
		}
		
		if($this->linksDisabled)
			Manialink::enableLinks();
		if($this->layout)
			Manialink::endFrame();
		Manialink::endFrame();
		
		// post filtering of the drawing process ...
		if($layout instanceof AbstractLayout)
			$layout->postFilter($this);
		
		return $zCur;
	}
	
	/**
	 * Removes all references to other objects.
	 * Recursively removes all connected resources.
	 */
	function destroy()
	{
		$this->clearComponents();
		foreach($this->parents as $parent)
			$parent->removeComponent($this);
		$this->parents = array();
		$this->layout = null;
		
		foreach($this->actions as $action)
			ActionHandler::getInstance()->deleteAction($action);
	}
	
	// TODO remove this part when PHP will have a refcount function or weak references
	private $actions = array();
	
	function createAction($callback)
	{
		$action = call_user_func_array(array(ActionHandler::getInstance(), 'createAction'), func_get_args());
		$this->actions[] = $action;
		return $action;
	}
	
	function __destruct()
	{
		$this->destroy();
	}
}

?>