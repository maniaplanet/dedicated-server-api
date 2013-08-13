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

use ManiaLib\Gui\Manialink;
use ManiaLive\Event\Dispatcher;
use ManiaLive\Features\Tick\Event as TickEvent;
use ManiaLive\Features\Tick\Listener as TickListener;

/**
 * Description of Window
 */
abstract class Window extends Container implements TickListener
{
	const Z_MIN = -60;
	const Z_MAXIMIZED = 0;
	const Z_MODAL = 25;
	const Z_MAX = 50;
	const Z_OFFSET = .1;

	const LAYER_NORMAL = 'normal';
	const LAYER_SCORES_TABLE = 'scorestable';

	/**
	 * In the screens at the spawn
	 */
	const LAYER_SPAWN_SCREEN = 'screenin3d';

	/**
	 * Above intros
	 */
	const LAYER_CUT_SCENE = 'cutscene';

	const RECIPIENT_ALL        = null;
	const RECIPIENT_PLAYERS    = true;
	const RECIPIENT_SPECTATORS = false;

	static private $singletons = array();
	static private $instancesByClass = array();
	static private $instancesByLoginAndClass = array();

	private $recipient;
	private $visibilities = array();
	private $timeout = false;
	private $linksDisabled = false;
	private $closeCallbacks = array();
	private $above = array();
	private $below = array();
	private $layer = self::LAYER_NORMAL;

	/**
	 * This will create one instance of the window for
	 * each player. If you dont want to use this feature
	 * you can deactivate it by setting singleton to false.
	 * @param Group|string|null $recipient
	 * @param bool $singleton
	 * @return Window
	 */
	static function Create($recipient = self::RECIPIENT_ALL, $singleton = true)
	{
		if($recipient === self::RECIPIENT_ALL)
			$recipient = Group::Get('all');
		else if($recipient === self::RECIPIENT_PLAYERS)
			$recipient = Group::Get('players');
		else if($recipient === self::RECIPIENT_SPECTATORS)
			$recipient = Group::Get('spectators');

		$className = get_called_class();
		$args = array_slice(func_get_args(), 2);
		$login = strval($recipient);

		if(!isset(self::$instancesByClass[$className]))
			self::$instancesByClass[$className] = array();
		if(!isset(self::$instancesByLoginAndClass[$login]))
			self::$instancesByLoginAndClass[$login] = array($className => array());
		else if(!isset(self::$instancesByLoginAndClass[$login][$className]))
			self::$instancesByLoginAndClass[$login][$className] = array();

		if(!$singleton)
			$instance = new static($recipient, $args);
		else if(isset(self::$singletons[$login]))
		{
			if(isset(self::$singletons[$login][$className]))
				return self::$singletons[$login][$className];
			else
			{
				$instance = new static($recipient, $args);
				self::$singletons[$login][$className] = $instance;
			}
		}
		else
		{
			$instance = new static($recipient, $args);
			self::$singletons[$login] = array($className => $instance);
		}

		self::$instancesByClass[$className][$instance->id] = $instance;
		self::$instancesByLoginAndClass[$login][$className][$instance->id] = $instance;
		return $instance;
	}

	/**
	 * Gets all currently opened instances of this
	 * window type.
	 * @return Window[]
	 */
	static function GetAll()
	{
		$className = get_called_class();
		$instances = array();

		foreach(self::$instancesByClass as $class => $windows)
			if($class == $className || is_subclass_of($class, $className))
				array_splice($instances, count($instances), 0, self::$instancesByClass[$class]);
		return $instances;
	}

	/**
	 * Gets all instances of this window type for a specific player
	 * (or group of players) that is (are) currently on the server.
	 * @param Group|string $recipient
	 * @return Window[]
	 */
	static function Get($recipient)
	{
		$className = get_called_class();
		$instances = array();
		$login = strval($recipient);

		if(isset(self::$instancesByLoginAndClass[$login]))
			foreach(self::$instancesByLoginAndClass[$login] as $class => $windows)
				if($class == $className || is_subclass_of($class, $className))
					array_splice($instances, count($instances), 0, self::$instancesByLoginAndClass[$login][$class]);
		return $instances;
	}

	/**
	 * Redraws all window instances that
	 * are currently shown on player screens.
	 */
	static function RedrawAll()
	{
		$className = get_called_class();

		foreach(self::$instancesByClass as $class => $windows)
			if($class == $className || is_subclass_of($class, $className))
				foreach($windows as $window)
					if($window->isVisible())
						$window->show();
	}

	/**
	 * Frees the memory that has been allocated for the player's window(s).
	 * If it is currently displayed it will also be closed.
	 * @param Group|string $recipient
	 */
	static function Erase($recipient)
	{
		$className = get_called_class();
		$login = strval($recipient);

		if(isset(self::$instancesByLoginAndClass[$login]))
			foreach(self::$instancesByLoginAndClass[$login] as $class => $windows)
				if($class == $className || is_subclass_of($class, $className))
					foreach($windows as $window)
						$window->destroy();
	}

	/**
	 * Frees memory for all windows of that type.
	 * Closes windows for the players where they
	 * currently are displayed to.
	 */
	static function EraseAll()
	{
		$className = get_called_class();

		foreach(self::$instancesByClass as $class => $windows)
			if($class == $className || is_subclass_of($class, $className))
				foreach($windows as $window)
					$window->destroy();
	}

	/**
	 * Can't be called from outside of the class
	 * to retrieve a new instance call Create.
	 * @param string $recipient
	 */
	final private function __construct($recipient, $args = array())
	{
		$this->recipient = $recipient;
		$this->id = spl_object_hash($this);
		if($this->recipient instanceof Group)
			foreach($this->recipient as $login)
				$this->visibilities[$login] = false;
		else
			$this->visibilities[$this->recipient] = false;

		if(empty($args))
			$this->onConstruct();
		else
			call_user_func_array(array($this, 'onConstruct'), $args);

		$this->posZ = null;
	}

	/**
	 * Use this method to initialize all subcomponents
	 * and add them to the Window's intern container.
	 */
	protected function onConstruct() {}

	/**
	 * @return string Whom this window is sent to.
	 */
	final public function getRecipient()
	{
		return $this->recipient;
	}

	/**
	 * Set the time before the interface will be hidden
	 * @param $seconds
	 */
	public function setTimeout($seconds)
	{
		$this->timeout = time() + $seconds;
		Dispatcher::register(TickEvent::getClass(), $this);
	}

	/**
	 * If the window will hide after a specific amount of time.
	 */
	public function getTimeout()
	{
		return !$this->timeout ? false : $this->timeout - time();
	}

	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Features/Tick/ManiaLive\Features\Tick.Listener::onTick()
	 */
	public function onTick()
	{
		if(time() >= $this->timeout)
		{
			$this->timeout = false;
			$this->hide();
			Dispatcher::unregister(TickEvent::getClass(), $this);
		}
	}

	final function disableLinks()
	{
		$this->linksDisabled = true;
	}

	final function enableLinks()
	{
		$this->linksDisabled = false;
	}

	final function areLinksDisabled()
	{
		return $this->linksDisabled;
	}

	/**
	 * Do something when you are closed!
	 * @param callback $callback
	 */
	final public function addCloseCallback($callback)
	{
		if(is_callable($callback))
			$this->closeCallbacks[] = $callback;
	}

	/**
	 * Moves this Window on the z-axis in front of the specified one.
	 * @param Window $window Reference window to move in front of.
	 */
	final public function moveAbove(Window $window)
	{
		if(isset($window->below[$this->id]))
		{
			unset($window->below[$this->id]);
			unset($this->above[$window->id]);
		}

		$this->below[$window->id] = $window;
		$window->above[$this->id] = $this;
	}

	/**
	 * Positions this window on the screen's center.
	 */
	final public function centerOnScreen()
	{
		$this->setPosition(0, 0);
		$this->setAlign('center', 'center');
	}

	/**
	 * Positions the window on the center of the windows
	 * that you can give as parameters.
	 * @param array[Window] $windows
	 */
	final public function centerOn($windows)
	{
		$minX = 0;
		$minY = 0;
		$maxX = 0;
		$maxY = 0;

		foreach($windows as $window)
		{
			if(!($window instanceof Window))
				continue;

			$minX = min($minX, $window->getBorderLeft());
			$minY = min($minY, $window->getBorderTop());
			$maxX = max($maxX, $window->getBorderRight());
			$maxY = max($maxY, $window->getBorderBottom());
		};

		// set position to the rectangle's center
		$this->setPosition(($minX + $maxX) / 2, ($minY + $maxY) / 2);
		$this->setAlign('center', 'center');
	}

	/**
	 * Simply show the window on the lowest z-layer.
	 * @param mixed $recipient Update or show window for specific player only.
	 */
	final public function show($recipient = null)
	{
		if(!($this->recipient instanceof Group && $this->recipient->contains($recipient)))
			$recipient = $this->recipient;

		$wasVisible = true;
		if(!($recipient instanceof Group || is_array($recipient)))
			$recipient = array($recipient);
		foreach($recipient as $login)
		{
			$wasVisible = $wasVisible && isset($this->visibilities[$login]) && $this->visibilities[$login];
			$this->visibilities[$login] = true;
		}

		GuiHandler::getInstance()->addToShow($this, $recipient);

		if(!$wasVisible)
			$this->onShow();
	}

	final public function showModal($recipient = null)
	{
		$this->centerOnScreen();

		if(!($this->recipient instanceof Group && $this->recipient->contains($recipient)))
			$recipient = $this->recipient;

		$wasVisible = true;
		if(!($recipient instanceof Group || is_array($recipient)))
			$recipient = array($recipient);
		foreach($recipient as $login)
		{
			$wasVisible = $wasVisible && isset($this->visibilities[$login]) && $this->visibilities[$login];
			$this->visibilities[$login] = true;
		}

		GuiHandler::getInstance()->addModal($this, $recipient);

		if(!$wasVisible)
			$this->onShow();
	}

	/**
	 * Closes the window and informs other windows about it.
	 * @param mixed $recipient
	 */
	final public function hide($recipient = null)
	{
		if(!($this->recipient instanceof Group && $this->recipient->contains($recipient)))
			$recipient = $this->recipient;

		$oldVisibilities = array();
		if(!($recipient instanceof Group || is_array($recipient)))
			$recipient = array($recipient);
		foreach($recipient as $login)
		{
			$oldVisibilities[$login] = !isset($this->visibilities[$login]) || $this->visibilities[$login];
			$this->visibilities[$login] = false;
		}

		GuiHandler::getInstance()->addToHide($this, $recipient);

		$onHideCalled = false;
		foreach($oldVisibilities as $login => $wasVisible)
		{
			if(!$onHideCalled && $wasVisible)
			{
				$this->onHide();
				$onHideCalled = true;
			}
			if($wasVisible)
				foreach($this->closeCallbacks as $callback)
					call_user_func($callback, $login, $this);
		}
	}

	final public function redraw($recipient = null)
	{
		if(!($this->recipient instanceof Group && $this->recipient->contains($recipient)))
			$recipient = $this->recipient;
		if(!($recipient instanceof Group || is_array($recipient)))
			$recipient = array($recipient);
		GuiHandler::getInstance()->addToRedraw($this, $recipient);
	}

	final public function save()
	{
		$this->onDraw();

		$posX = $this->posX;
		$posY = $this->posY;

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

		Manialink::beginFrame($posX, $posY, $this->posZ, $this->scale);
		Manialink::setFrameId($this->id);
		if($this->linksDisabled)
			Manialink::disableLinks();

		// render each element contained by the control and set z values...
		$zCur = 0;
		foreach($this->getComponents() as $component)
		{
			if ($component->getPosZ() === null)
			{
				$component->setPosZ($zCur);
				if($component instanceof Control)
					$zCur += $component->save();
				else
				{
					$component->save();
					$zCur += self::Z_OFFSET;
				}
			}
			else
			{
				$component->save();
			}
		}

		if($this->linksDisabled)
			Manialink::enableLinks();
		Manialink::endFrame();
	}

	final function getMinZ()
	{
		$minZ = self::Z_MIN;
		foreach($this->below as $window)
			$minZ = max($minZ, $window->getMaxZ() + self::Z_OFFSET);
		return $minZ;
	}

	final function getMaxZ()
	{
		return $this->getMinZ() + self::Z_OFFSET * $this->countElements();
	}

	final function setLayer($layer)
	{
		$this->layer = $layer;
	}

	final function getLayer()
	{
		return $this->layer;
	}

	/**
	 * Actions that need to be processed before the Window is being
	 * showed. This includes eg. resizing and positioning of Elements and Controls.
	 */
	protected function onDraw() {}

	/**
	 * Cleaning up data and resetting of fields when the Window is being removed
	 * removed from the screen.
	 */
	protected function onHide() {}

	/**
	 * Register to dispatcher for events that should only be executed when the
	 * Window is being showed.
	 */
	protected function onShow() {}

	/**
	 * Remove all references from this Window.
	 * Unregister events and remove references to this Window from components.
	 */
	public function destroy()
	{
		$this->hide();
		$this->clearComponents();

		foreach($this->below as $below)
			unset($below->above[$this->id]);
		foreach($this->above as $above)
			unset($above->below[$this->id]);

		$this->closeCallbacks = array();
		$this->below = array();
		$this->above = array();

		// remove from intern window list ...
		$className = get_called_class();
		unset(self::$singletons[strval($this->recipient)][$className]);
		unset(self::$instancesByClass[$className][$this->id]);
		unset(self::$instancesByLoginAndClass[strval($this->recipient)][$className][$this->id]);

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
}

?>