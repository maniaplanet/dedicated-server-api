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

use ManiaLive\Utilities\Console;
use ManiaLib\Gui\Drawable;
use ManiaLib\Gui\Layouts\AbstractLayout;
use ManiaLib\Gui\Manialink;

/**
 * Control class, use this to compose existing Elements.
 * You can also add Controls that already exist to attach Elements or to
 * extend functionality.
 * Override the initializeComponents to instanciate Controls and Elements.
 * Use the onDraw method to prepare Elements and Controls for being displayed.
 * Keep in mind that calling the callback method is only possible in the onDraw method due to
 * it making use of the getWindow method.
 * 
 * @author Florian Schnell
 */
abstract class Control extends Container
	implements Drawable, Containable
{
	protected $parent;
	protected $zCur;
	protected $zForced;
	/**
	 * @var \ManiaLive\Gui\Toolkit\Layouts\AbstractLayout
	 */
	protected $layout;
	protected $scale;
	protected $initializing;
	protected $fid;
	protected $params;
	protected $window;
	
	final function __construct()
	{	
		$this->zCur = 0;
		$this->scale = null;
		$this->layout = null;
		$this->params = func_get_args();
		$this->window = null;
		
		$this->initializing = true;
		$this->initializeComponents();
		$this->initializing = false;
	}
	
	/**
	 * Use this method to initialize all subcomponents
	 * and add them to the Control's intern container.
	 */
	abstract protected function initializeComponents();
	
	/**
	 * Get a construct parameter.
	 */
	final protected function getParam($num, $default = null)
	{
		return (isset($this->params[$num]) ? $this->params[$num] : $default);
	}
	
	/**
	 * This will prevent the windowing system from overriding
	 * the z-value for this control and its subelements.
	 * @param unknown_type $z
	 */
	function forceZ($z)
	{
		$this->zForced = $z;
	}
	
	/**
	 * Apply a Layout onto all subcontrols.
	 * @param AbstractLayout $layout
	 */
	function applyLayout(AbstractLayout $layout)
	{
		$this->layout = $layout;
	}
	
	/**
	 * Remove Layout from Control.
	 */
	function removeLayout()
	{
		$this->layout = null;
	}
	
	/**
	 * If this control is added to a container, then assign
	 * the container as the control's parent.
	 * We also determine the control's parent window.
	 * @param Container $target The Container object to which the Control is added.
	 */
	function onIsAdded(Container $target)
	{
		$this->parent = $target;
		
		if ($target instanceof WindowDisplayable)
		{
			$this->announceWindow($target->window);
		}
		elseif ($target instanceof Control)
		{
			if ($target->getWindow() != null)
				$this->announceWindow($target->getWindow());
		}
	}
	
	/**
	 * If this component knows its parent window
	 * it will announce it to all its children.
	 * @param $window
	 */
	function announceWindow(Window $window)
	{
		$this->window = $window;
		
		foreach ($this->components as $child)
		{
			if ($child instanceof Control)
			{
				$child->announceWindow($window);
			}
		}
	}
	
	/**
	 * Uses the parent Window's function to generate a callback function that
	 * can be assigned as an Element's action.
	 * @param callback $callback Function that is being executed when this action is clicked.
	 * @return integer The action ID linked to the callback function.
	 */
	protected function callback($callback)
	{
		$args = func_get_args();
		array_shift($args);
		
		if (!is_array($callback))
		{
			$callback = array($this, $callback);
		}
		
		array_splice($args, 0, 0, array($callback));
			
		return call_user_func_array(array($this->getWindow(), 'callback'), $args);
	}
	
	/**
	 * This can only be called from the onDraw method when all parents are set
	 * and we can move through the tree to find the parent Window.
	 * @return ManiaLive\Gui\Windowing\Window The Window this Control is assigned to.
	 */
	public function getWindow()
	{
		return $this->window;
	}
	
	/**
	 * Gets a value for this Control which is specific to the player
	 * which is currently viewing it.
	 * The value needs to be saved with setPlayerValue before it can be read.
	 * @param string $name Name of the stored value.
	 */
	protected function getPlayerValue($name, $default = null)
	{
		return $this->getWindow()->getPlayerValue($name, $default);
	}
	
	/**
	 * Sets a value for the player which this Control is currently displayed to.
	 * @param string $name Name of the value, you can use this to retrieve the value again using getPlayerValue.
	 * @param mixed $value The value to store.
	 */
	protected function setPlayerValue($name, $value)
	{
		$this->getWindow()->setPlayerValue($name, $value);
	}
	
	/**
	 * Override this method in subclasses to perform actions
	 * before the Control is draw onto the screen.
	 */
	protected function beforeDraw() {}
	
	/**
	 * Override this method in subclasses to perform actions
	 * after the Control has been drawn onto the screen.
	 */
	protected function afterDraw() {}
	
	/**
	 * Renders the Control and all its Subelements/Subcontrols.
	 * Sets all Z-Indexes accordingly to the order of the items.
	 */
	final function save()
	{
		$posx = 0;
		$posy = 0;
		
		if (!$this->isVisible())
		{
			return;
		}
		
		// apply any layout to the underlying elements ...
		$layout = end(Manialink::$parentLayouts);
		if($layout instanceof AbstractLayout)
		{
			$layout->preFilter($this);
			
			$posx = $layout->xIndex;
			$posy = -$layout->yIndex;
		}
		
		// apply prefilter ...
		$this->beforeDraw();
		
		// reset z depth ...
		$this->zCur = ($this->zForced ? $this->zForced : 0);
		
		// prepare aligning ...
		if ($this instanceof Control)
		{
			// horizontal alignment ...
			if ($this->halign == 'right')
			{
				$posx += $this->posX - $this->getRealSizeX();
			}
			elseif ($this->halign == 'center')
			{
				$posx += $this->posX - $this->getRealSizeX() / 2;
			}
			else
			{
				$posx += $this->posX;
			}
				
			// vertical alignment ...
			if ($this->valign == 'bottom')
			{
				$posy += $this->posY - $this->getRealSizeY();
			}
			elseif ($this->valign == 'center')
			{
				$posy += $this->posY - $this->getRealSizeY() / 2;
			}
			else
			{
				$posy += $this->posY;
			}
		}
		else
		{
			// for elements this behavior is implemented in the game ...
			$posx += $this->posX;
			$posy += $this->posY;
		}
		
		if ($this->layout)
		{
			$this->layout->setSizeX($this->sizeX);
			$this->layout->setSizeY($this->sizeY);
			Manialink::beginFrame($posx, $posy, $this->posZ, $this->scale, clone $this->layout);
		}
		else 
		{
			Manialink::beginFrame($posx, $posy, $this->posZ, $this->scale);
		}
		
		// render each element contained by the control and set z values ...
		foreach ($this->components as $component)
		{
			if ($component instanceof Control)
			{
				$component->setPositionZ($this->zCur);
				$this->zCur += $component->save();
			}
			else
			{
				$component->setPositionZ($this->zCur);
				$this->zCur += Z_OFFSET;
				$component->save();
			}
		}
		
		Manialink::endFrame();
		
		// post filtering of the drawing process ...
		$this->afterDraw();
		
		// Layout post filtering
		if($layout instanceof AbstractLayout)
		{
			$layout->postFilter($this);
		}
		
		return $this->zCur;
	}
	
	/**
	 * Removes all references to other objects.
	 * Recursively removes all connected resources.
	 */
	function destroy()
	{
		$this->layout = null;
		$this->parent = null;
		
		foreach ($this->components as $component)
		{
			if ($component instanceof Control)
			{
				$component->destroy();
			}
			$component = null;
		}
		$this->components = array();
	}
}

class Exception extends \Exception {}
?>