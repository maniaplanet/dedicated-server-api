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
	protected $params;
	/**
	 * @var \ManiaLive\Gui\Windowing\Window
	 */
	protected $window;
	protected $linksDisabled;
	
//	static $controls;
	
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
		
//		self::$controls[spl_object_hash($this)] = get_class($this);
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
	
	function disableLinks()
	{
		$this->linksDisabled = true;
	}
	
	function enableLinks()
	{
		$this->linksDisabled = false;
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
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Gui/Windowing/ManiaLive\Gui\Windowing.Containable::onIsAdded()
	 */
	function onIsAdded(Container $target)
	{
//		echo "added " . get_class($this) . " \n";
		
		$this->parent = $target;
		
		if ($target instanceof WindowDisplayable)
		{
			if ($this->window != $target->window)
				$this->announceWindow($target->window);
		}
		elseif ($target instanceof Control)
		{
			if ($target->getWindow() != null
				&& $target->getWindow() != $this->window)
				$this->announceWindow($target->getWindow());
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Gui/Windowing/ManiaLive\Gui\Windowing.Containable::onIsRemoved()
	 */
	function onIsRemoved(Container $target)
	{
		$this->announceWindow(null);
		$this->parent = null;
	}
	
	/**
	 * If this component knows its parent window
	 * it will announce it to all its children.
	 * @param $window
	 */
	function announceWindow($window)
	{
		if ($this->window != null && $this->window !== $window)
		{
			$this->window->removeCallbacks($this);
		}
		
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
		if ($this->window)
		{
			$args = func_get_args();
			array_shift($args);
			
			if (!is_array($callback))
			{
				$callback = array($this, $callback);
			}
			
			array_unshift($args, $callback);
			
			return call_user_func_array(array($this->window, 'callback'), $args);
		}
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
	 * Force a redraw of this component on the
	 * screens it is currently showed.
	 */
	public function redraw()
	{
		if ($this->window)
		{
			$this->window->show();
		}
	}
	
	/**
	 * Gets a value for this Control which is specific to the player
	 * which is currently viewing it.
	 * The value needs to be saved with setPlayerValue before it can be read.
	 * @param string $name Name of the stored value.
	 */
	protected function getPlayerValue($name, $default = null)
	{
		return $this->window->getPlayerValue($name, $default);
	}
	
	/**
	 * Sets a value for the player which this Control is currently displayed to.
	 * @param string $name Name of the value, you can use this to retrieve the value again using getPlayerValue.
	 * @param mixed $value The value to store.
	 */
	protected function setPlayerValue($name, $value)
	{
		$this->window->setPlayerValue($name, $value);
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
		
		if ($this->linksDisabled)
			Manialink::disableLinks();
		
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
		
		if ($this->linksDisabled)
			Manialink::enableLinks();
		
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
//		echo "< unloading " . get_class($this) . "\n";
		
		foreach ($this->components as $component)
		{
			if ($component instanceof Control)
			{
				$component->destroy();
			}
		}
		
		if ($this->window)
		{
//			echo "removing callbacks!\n";
			$this->window->removeCallbacks($this);
		}
		
		$this->parent = null;
		$this->components = array();
		$this->componentList = array();
		$this->layout = null;
		$this->params = array();
		$this->window = null;
	}
	
	function __destruct()
	{
//		unset(self::$controls[spl_object_hash($this)]);
//		echo "new control count:" . count(self::$controls) . "\n";
//		echo "<< desctructing " . get_class($this) . "\n";
	}
}

class Exception extends \Exception {}
?>