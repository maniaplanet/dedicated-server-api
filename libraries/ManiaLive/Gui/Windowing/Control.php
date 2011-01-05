<?php

namespace ManiaLive\Gui\Windowing;

use ManiaLive\Gui\Toolkit\Drawable;
use ManiaLive\Gui\Toolkit\Layouts\AbstractLayout;
use ManiaLive\Gui\Toolkit\Manialink;

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
 * @copyright 2010 NADEO
 */
abstract class Control extends Container implements Drawable, Containable
{
	protected $parent;
	protected $z_cur;
	protected $z_forced;
	/**
	 * @var \ManiaLive\Gui\Toolkit\Layouts\AbstractLayout
	 */
	protected $layout;
	protected $scale;
	protected $initializing;
	protected $fid;
	protected $params;
	
	static $controlcount = 0;
	static $names;
	
	final function __construct()
	{	
		$this->z_cur = 0;
		$this->scale = null;
		$this->layout = null;
		$this->params = func_get_args();
		
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
	final protected function getParam($num)
	{
		if (isset($this->params[$num]))
		{
			return $this->params[$num];
		}
		else
		{
			return null;
		}
	}
	
	/**
	 * This will prevent the windowing system from overriding
	 * the z-value for this control and its subelements.
	 * @param unknown_type $z
	 */
	function forceZ($z)
	{
		$this->z_forced = $z;
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
	 * If this Control is added to a Container, then assign the Container as the
	 * Control's parent.
	 * @param Container $target The Container object to which the Control is added.
	 */
	function onIsAdded(Container $target)
	{
		$this->parent = $target;
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
			$callback = array($this, $callback);
		
		array_splice($args, 0, 0, array($callback));
			
		return call_user_func_array(array($this->getWindow(), 'callback'), $args);
	}
	
	/**
	 * This can only be called from the onDraw method when all parents are set
	 * and we can move through the tree to find the parent Window.
	 * @return ManiaLive\Gui\Windowing\Window The Window this Control is assigned to.
	 */
	protected function getWindow()
	{
		if ($this->initializing)
			throw new Exception('You can not call ' . __FUNCTION__ . ' during the initializeComponents method within a Control!');
		
		$me = $this;
		while (isset($me->parent)) $me = $me->parent;
		return $me->window;
	}
	
	/**
	 * Gets a value for this Control which is specific to the player
	 * which is currently viewing it.
	 * The value needs to be saved with setPlayerValue before it can be read.
	 * @param string $name Name of the stored value.
	 */
	protected function getPlayerValue($name)
	{
		return $this->getWindow()->getPlayerValue($name);
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
		if (!$this->isVisible())
			return;
			
		// reset z depth ...
		if ($this->z_forced)
			$this->z_cur = $this->z_forced;
		else
			$this->z_cur = 0;
		
		// apply prefilter ...
		$this->beforeDraw();
		
		// prepare aligning ...
		if ($this instanceof Control)
		{
			// horizontal alignment ...
			if ($this->halign == 'right')
				$posx = $this->posX - $this->getRealSizeX();
			elseif ($this->halign == 'center')
				$posx = $this->posX - $this->getRealSizeX() / 2;
			else
				$posx = $this->posX;
				
			// vertical alignment ...
			if ($this->valign == 'bottom')
				$posy = $this->posY - $this->getRealSizeY();
			elseif ($this->valign == 'center')
				$posy = $this->posY - $this->getRealSizeY() / 2;
			else
				$posy = $this->posY;
		}
		else
		{
			// for elements this behavior is implemented in the game ...
			$posx = $this->posX;
			$posy = $this->posY;
		}
		
		// apply any layout to the underlying elements ...
		$layout = end(Manialink::$parentLayouts);
		if($layout instanceof AbstractLayout)
		{
			$layout->preFilter($this);
			
			$posx += $layout->xIndex;
			$posy -= $layout->yIndex;
		}
		
		if ($this->layout)
		{
			$this->layout->setSizeX($this->sizeX);
			$this->layout->setSizeY($this->sizeY);
			Manialink::beginFrame($posx, $posy, $this->posZ, $this->scale, clone $this->layout);
		}
		else 
			Manialink::beginFrame($posx, $posy, $this->posZ, $this->scale);
		
		// render each element contained by the control and set z values ...
		foreach ($this->components as $component)
		{
			if ($component instanceof Control)
			{
				$component->setPositionZ($this->z_cur);
				$this->z_cur += $component->save();
			}
			else
			{
				$component->setPositionZ($this->z_cur);
				$this->z_cur += Z_OFFSET;
				$component->save();
			}
		}
		
		Manialink::endFrame();
		
		// Layout post filtering
		if($layout instanceof AbstractLayout)
		{
			$layout->postFilter($this);
		}
		
		// post filtering of the drawing process ...
		$this->afterDraw();
		
		return $this->z_cur;
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
				$component->destroy();
				$component = null;
		}
		$this->components = array();
	}
}

class Exception extends \Exception {}
?>