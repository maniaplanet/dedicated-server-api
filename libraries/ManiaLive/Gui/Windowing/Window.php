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

use ManiaLive\Gui\Windowing\Controls\Frame;
use ManiaLib\Gui\Elements\BgsPlayerCard;
use ManiaLib\Gui\Drawable;
use ManiaLib\Gui\Component;
use ManiaLib\Gui\Element;
use ManiaLive\Gui\Handler\IDGenerator;
use ManiaLive\Utilities\Console;
use ManiaLive\Data\Storage;
use ManiaLive\Gui\Displayables\Blank;
use ManiaLive\Event\Dispatcher;
use ManiaLive\Gui\Toolkit\Cards\Leaderboard;
use ManiaLive\Gui\Handler\GuiHandler;

/**
 * The minimum possible z-value.
 * This will be the starting value for the most bottom window.
 * @var integer
 */
const Z_MIN = -32;

/**
 * Increase the z-offset by this value on each step.
 * @var float
 */
const Z_OFFSET = 0.1;

/**
 * The maximal z-offset that can be achieved.
 * Reaching this means that there are too many elements drawn above each other.
 * @var integer
 */
const Z_MAX = 32;

/**
 * Use this class to display information on the screen without
 * haveing to care about z-Indexing.
 * It also offers an easier way to handle controls, actions and positioning.
 * Use the initializeComponents method to instanciate Elements and Controls.
 * The onShow method prepares the Window for the Screen and can be used to configure Elements and Controls.
 * 
 * @author Florian Schnell
 */
abstract class Window extends Container implements
	\ManiaLive\Gui\Handler\Listener,
	\ManiaLive\Features\Tick\Listener
{
	private $uid;
	private $id;
	private $callbacks;
	private $header;
	
	protected $login;
	protected $isHidden;
	protected $linksDeactivated;
	protected $view;
	protected $playerValues;
	protected $autohide;
	protected $useClassicPositioning;
	
	protected $closeCallbacks;
	
	public $above;
	public $below;
	public $uptodate;
	
	static $instances = array();
	static $instancesNonSingleton = array();
	static $instancesByClass = array();
	
	/**
	 * this will address the window to all
	 * players that are currently on the server.
	 * @var string
	 */
	const RECIPIENT_ALL = '$all';
	
	/**
	 * Can't be called from outside of the class
	 * to retrieve a new instance call Create.
	 * @param string $login
	 */
	protected function __construct($login)
	{
		$this->uid = uniqid();
		$this->useClassicPositioning = false;
		$this->autohide = false;
		$this->playerValues = array();
		
		// manage z indexing
		$this->below = array();
		$this->above = array();
		
		$this->closeCallbacks = array();
		$this->linksDeactivated = false;
		$this->callbacks = array();
		$this->login = $login;
		$this->isHidden = true;
		$this->id = \ManiaLive\Gui\Handler\IDGenerator::generateManialinkID();
		Dispatcher::register(\ManiaLive\Gui\Handler\Event::getClass(), $this);
		$this->initializeComponents();
		$this->posZ = Z_MIN;
	}
	
	/**
	 * This will create one instance of the window for
	 * each player. If you dont want to use this feature
	 * you can deactivate it by setting singleton to false.
	 * @param string $login
	 * @return \ManiaLive\Gui\Windowing\Window
	 */
	static function Create($login, $singleton = true)
	{
		$class_name = get_called_class();
		if (!isset(self::$instancesByClass[$class_name]))
		{
			self::$instancesByClass[$class_name] = array();
			if (!isset(self::$instancesByClass[$class_name][$login]))
			{
				self::$instancesByClass[$class_name][$login] = array();
			}
		}
		
		if (!$singleton)
		{
			$instance  = new static($login);
			self::$instancesNonSingleton[$login][$instance->getUid()] = $instance;
			self::$instancesByClass[$class_name][$login][$instance->getUid()] = $instance;
			return $instance;
		}
		
		if (isset(self::$instances[$login]))
		{
			if (isset(self::$instances[$login][$class_name]))
			{
				return self::$instances[$login][$class_name];
			}
			else
			{
				$instance = new static($login);
				self::$instances[$login][$class_name] = $instance;
				self::$instancesByClass[$class_name][$login][$instance->getUid()] = $instance;
				return $instance;
			}
		}
		else
		{
			$instance = new static($login);
			
			self::$instances[$login] = array
			(
				$class_name => $instance
			);
			self::$instancesByClass[$class_name][$login][$instance->getUid()] = $instance;
			
			return $instance;
		}
	}
	
	/**
	 * Gets all currently opened instances of this
	 * window type.
	 */
	static function GetAll()
	{
		$instances = array();
		$class_name = get_called_class();
		
		if (isset(self::$instancesByClass[$class_name]))
		{
			foreach (self::$instancesByClass[$class_name] as $login => $windows)
			{
				$instances = array_merge($instances, $windows);
			}
		}
		
		return $instances;
	}
	
	/**
	 * Gets all instances of this window type for
	 * a specific player that is currently on
	 * the server.
	 */
	static function Get($login)
	{
		$pclass = get_called_class();
		
		$windows = array();
		
		foreach (self::$instancesByClass as $class_name => $stack)
		{
			if (is_subclass_of($class_name, $pclass))
			{
				if (isset(self::$instancesByClass[$class_name][$login]))
				{
					$windows = array_merge($windows, self::$instancesByClass[$class_name][$login]);
				}
			}
		}
		
		return $windows;
	}
	
	/**
	 * Redraws all window instances that
	 * are currently shown on player screens.
	 */
	static function Redraw()
	{
		$windows = self::GetAll();
		foreach ($windows as $window)
		{
			if ($window->isShown())
			{
				$window->show();
			}
		}
	}
	
	/**
	 * Frees the memory that has been allocated
	 * for the player's window(s).
	 * If it is currently displayed it will also be
	 * closed.
	 * @param string $login
	 */
	static function Erase($login)
	{
		$class_name = get_called_class();
		
		if (isset(self::$instances[$login][$class_name]))
		{
			$window = self::$instances[$login][$class_name];
			$window->hide();
			$window->setCloseCallback(array($window, 'destroy'));
		}
		
		if (isset(self::$instancesNonSingleton[$login]))
		{
			foreach (self::$instancesNonSingleton[$login] as $window)
			{
				if (is_subclass_of($window, $class_name))
				{
					$window->hide();
					$window->setCloseCallback(array($window, 'destroy'));
				}
			}
		}
	}
	
	/**
	 * Frees memory for all windows of that type.
	 * Closes windows for the players where they
	 * currently are displayed to.
	 */
	static function EraseAll()
	{
		$class_name = get_called_class();
		
		foreach (self::$instances as $login => $windows)
		{
			foreach ($windows as $class => $window)
			{
				if ($class == $class_name)
				{
					if ($window->isShown())
					{
						$window->hide();
						$window->setCloseCallback(array($window, 'destroy'));
					}
					else
					{
						$window->destroy();
					}
				}
			}
		}
		
		foreach (self::$instancesNonSingleton as $login => $windows)
		{
			foreach ($windows as $uid => $window)
			{
				if (get_class($window) == $class_name)
				{
					$window->hide();
					$window->setCloseCallback(array($window, 'destroy'));
				}
			}
		}
		
		WindowHandler::closeWindowThumbs(get_called_class());
	}
	
	/**
	 * search for highest z value in all windows.
	 * @param unknown_type $login
	 */
	static function GetTopZ($login)
	{
		$exceptions = func_get_args();
		array_shift($exceptions);
		$zValues = array(\ManiaLive\Gui\Windowing\Z_MIN);
		$classes = array('');
		
		if (isset(self::$instances[$login]))
		{
			foreach (self::$instances[$login] as $window)
			{
				if ($window->isShown())
				{
					if (!in_array($window, $exceptions))
					{
						$zValues[] = $window->getMaxZ();
					}
				}
			}
		}
		
		if (isset(self::$instancesNonSingleton[$login]))
		{
			foreach (self::$instancesNonSingleton[$login] as $window)
			{
				if ($window->isShown())
				{
					if (!in_array($window, $exceptions))
					{
						$zValues[] = $window->getMaxZ();
					}
				}
			}
		}
		
		return max($zValues);
	}
	
	/**
	 * Use this method to initialize all subcomponents
	 * and add them to the Window's intern container.
	 */
	abstract protected function initializeComponents();
	
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
	 * @param $seconds
	 * @deprecated
	 */
	public function setAutohide($seconds)
	{
		$this->setTimeout($seconds);
	}
	
	/**
	 * Set the time before the interface will be hidden
	 * @param $seconds
	 */
	public function setTimeout($seconds)
	{
		$this->autohide = time() + $seconds;
		Dispatcher::register(\ManiaLive\Features\Tick\Event::getClass(), $this);
	}
	
	/**
	 * If the window will hide after a specific amount of time.
	 */
	public function getAutohide()
	{
		return (!$this->autohide ? false : $this->autohide - time());
	}
	
	/**
	 * Dont use the inverted y-axis for positioning.
	 * @param bool $bool
	 */
	public function setClassicPositioning($bool)
	{
		$this->useClassicPositioning = $bool;
	}
	
	/**
	 * Returns whether the y-axis is inverted.
	 * @return bool
	 */
	public function getClassicPositioning()
	{
		return $this->useClassicPositioning;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Features/Tick/ManiaLive\Features\Tick.Listener::onTick()
	 */
	public function onTick()
	{
		if (time() >= $this->autohide)
		{
			$this->autohide = false;
			$this->hide();
			Dispatcher::unregister(\ManiaLive\Features\Tick\Event::getClass(), $this);
		}
	}
	
	/**
	 * Creates a callback linked to an ActionId.
	 * @param callback $callback
	 * @param mixed $params, additional optional parameters
	 * @return integer ActionId that can be assigned to any Element.
	 */
	public function callback($callback)
	{
		$args = func_get_args();
		array_shift($args);
		
		// there is no callback specified!
		if ($callback == null)
		{
			return;
		}
		
		// prepare ...
		if (!is_array($callback))
		{
			$callback = array($this, $callback);
		}
		
		// add parameters to callback ...
		$callback = array($callback, $args);
		
		// search if this callback has an id yet ...
		$action = array_search($callback, $this->callbacks);
		if ($action === false)
		{
			$action = IDGenerator::generateActionID($this->id);
			$this->callbacks[$action] = $callback;
		}
		
		return $action;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Gui/Handler/ManiaLive\Gui\Handler.Listener::onActionClick()
	 */
	public function onActionClick($login, $action)
	{
		if (isset($this->callbacks[$action]))
		{
			$maximized = WindowHandler::getMaximized($login);
			$dialog = WindowHandler::getDialog($login);

			if (($dialog === false && ($maximized === false
				|| $maximized === $this)) || $dialog === $this)
			{
				$params = array($login);
				array_splice($params, count($params), 0, $this->callbacks[$action][1]);
				call_user_func_array($this->callbacks[$action][0], $params);
			}
		}
	}
	
	/**
	 * Positions this window on the screen's center.
	 */
	public function centerOnScreen()
	{
		$this->posX = -$this->sizeX / 2;
		$this->posY = -$this->sizeY / 2 - 6;
	}
	
	/**
	 * Positions the window on the center of the windows
	 * that you can give as parameters.
	 * @param Window $window1
	 * @param Window , ...
	 */
	public function centerOn()
	{
		$args = func_get_args();
		
		$minX = 0;
		$minY = 0;
		$maxX= 0;
		$maxY = 0;
		
		foreach ($args as $window)
		{
			if (!($window instanceof Window))
			{
				continue;
			}
			
			if ($window->getPosX() < $minX)
			{
				$minX = $window->getBorderLeft();
			}
				
			if ($window->getBorderTop() < $minY)
			{
				$minY = $window->getBorderTop();
			}
		
			if ($window->getBorderRight() > $maxX)
			{
				$maxX = $window->getBorderRight();
			}
				
			if ($window->getBorderBottom() > $maxY)
			{
				$maxY = $window->getBorderBottom();
			}
		};
		
		// set position to the rectangle's center
		$this->setPositionX(($minX + $maxX) / 2 - $this->getSizeX() / 2);
		$this->setPositionY(($minY + $maxY) / 2 - $this->getSizeY() / 2);
	}
	
	/**
	 * Positions the window on the underlying window's center.
	 */
	public function centerOnBelow()
	{
		call_user_func_array(array($this, 'centerOn'), $this->below);
	}
	
	/**
	 * Moves this Window on the z-axis in front of the specified one.
	 * @param Window $window Reference window to move in front of.
	 */
	public function moveAbove(Window $window)
	{	
		// if that window is above this one
		if (isset($window->below[$this->uid]))
		{
			// then we do a swap!
			unset($window->below[$this->uid]);
			unset($this->above[$window->getUid()]);
		}
		
		$this->below[$window->getUid()] = $window;
		$window->above[$this->uid] = $this;
	}
	
	/**
	 * Do something when you are closed!
	 * @param callback $callback
	 * @deprecated
	 */
	public function setCloseCallback($callback)
	{
		$this->addCloseCallback($callback);
	}
	
	/**
	 * Do something when you are closed!
	 * @param callback $callback
	 */
	public function addCloseCallback($callback)
	{
		if (is_callable($callback))
		{
			$this->closeCallbacks[] = $callback;
		}
	}
	
	/**
	 * Don't call this from outside!
	 * @internal
	 */
	public function render($login)
	{
		// get group ...
		if ($login == Window::RECIPIENT_ALL)
		{
			$group = GuiHandler::getInstance()->getGroup();
		}
		else
		{
			$player = Storage::getInstance()->getPlayerObject($login);
			if (!$player)
			{
				return;
			}
			$group = GuiHandler::getInstance()->getGroup($player);
		}
		
		// show the window ...
		if ($this->isHidden)
		{
			// invoke close callback
			foreach ($this->closeCallbacks as $callback)
			{
				call_user_func_array($callback, array($login, $this));
			}
			
			$group->displayableGroup->addDisplayable(new Blank($this->id));
		}
		else
		{
			// generate view object ...
			$this->initDisplayable();
			
			$group->displayableGroup->addDisplayable($this->view);
		}
		
		$this->posZ = Z_MIN;
	}
	
	/**
	 * Simply show the window on the lowest z-layer.
	 * @param string $login Update or show window for specific player only.
	 */
	public function show($login = self::RECIPIENT_ALL)
	{
		$this->uptodate = false;
		
		if ($this->login != self::RECIPIENT_ALL)
		{
			$login = $this->login;
		}
		
		$state = $this->isHidden;
		$this->isHidden = false;
		
		if (WindowHandler::add($this, $login))
		{	
			// call recover function when Window state changes
			// from hidden to visible
			if ($state)
			{
				$this->onShow();
				Dispatcher::dispatch(new Event($this, Event::ON_WINDOW_RECOVER, $login));
			}
			
			$this->isHidden = false;
		}
		else
		{
			$this->isHidden = $state;
		}
	}
	
	/**
	 * Closes the window and informs other windows about it.
	 * @param string $login
	 */
	public function hide($login = self::RECIPIENT_ALL)
	{
		$this->uptodate = false;
		
		if ($this->login != self::RECIPIENT_ALL)
		{
			$login = $this->login;
		}
		
		$state = $this->isHidden;
		$this->isHidden = true;
		
		// add to drawstack button ...
		if (WindowHandler::add($this, $login))
		{
			if (WindowHandler::getDialog($login) === $this)
			{
				WindowHandler::$dialogRefreshed = false;
			}
			
			// invoke cleanup function ..
			$this->onHide();
			
			// window is closed ...
			Dispatcher::dispatch(new Event($this, Event::ON_WINDOW_CLOSE, $login));
		}
		else
		{
			$this->isHidden = $state;
		}
	}
	
	/**
	 * Show this window above the given one and deactivate all Manialinks
	 * for the time this window is being displayed.
	 * @param Window $window
	 */
	public function showDialog(Window $window, $callback = null)
	{
		// set close callback if specified
		if ($callback)
		{
			$window->addCloseCallback(array($this, $callback));
		}
		
		// move the dialog above the current window
		$window->moveAbove($this);
		$window->centerOnBelow();
		
		// give the dialog to the windowing system
		// this will care of the dialog treatment
		WindowHandler::showDialog($window);
	}
	
	/**
	 * Creates new displayable and prepares it for being send
	 * to the screen.
	 */
	protected function initDisplayable()
	{
		$this->isHidden = false;
		
		// delete old view references ...
		if ($this->view)
		{
			$this->view->window = null;
		}
		
		// generate displayable ...
		$this->view = new WindowDisplayable();
		$this->view->setScale($this->getScale());
		$this->view->window = $this;
		
		// the windowcontroller prepares the view ...
		$this->onDraw();
		
		$this->view->setSize($this->sizeX, $this->sizeY);
		$this->view->setPosition($this->posX, $this->posY, $this->posZ);
		
		// assign components to the view ..
		foreach ($this->components as $component)
		{
			$this->view->addComponent($component);
		}
	}
	
	/**
	 * Stores a value for a specific player.
	 * @param unknown_type $name
	 * @param unknown_type $value
	 */
	public function setPlayerValue($name, $value)
	{
		if (!isset($this->playerValues[$this->getRecipient()]))
		{
			$this->playerValues[$this->getRecipient()] = array();
		}
			
		$this->playerValues[$this->getRecipient()][$name] = $value;
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $name
	 */
	public function getPlayerValue($name, $default = null)
	{
		if (isset($this->playerValues[$this->getRecipient()][$name]))
		{
			return $this->playerValues[$this->getRecipient()][$name];
		}
		else
		{
			return $default;
		}
	}
	
	/**
	 * Sets an Element, that will be drawn every
	 * time before the window itself.
	 * @param \ManiaLive\Gui\Toolkit\Component $header
	 */
	protected function setHeaderElement(Drawable $header)
	{
		$this->header = $header;
	}
	
	/**
	 * Returns the current header Element.
	 * @return \ManiaLive\Gui\Toolkit\Component
	 */
	public function getHeaderElement()
	{
		return $this->header;
	}
	
	/**
	 * Deactivates all links for this whole Window
	 * and all its subcomponents.
	 */
	public function deactivateLinks()
	{
		$this->linksDeactivated = true;
	}
	
	/**
	 * Activates all links for this whole Window
	 * and all its subcomponents.
	 */
	public function activateLinks()
	{
		$this->linksDeactivated = false;
	}
	
	/**
	 * @return bool Are the links of this window disabled?
	 */
	public function getLinksDeactivated()
	{
		return $this->linksDeactivated;
	}
	
	/**
	 * @return array[Window] Returns all windows underlying.
	 */
	public function getWindowsBelow()
	{
		return $this->below;
	}
	
	/**
	 * @return array[Window] Returns all windows that lie above this one.
	 */
	public function getWindowsAbove()
	{
		return $this->above;
	}
	
	/**
	 * @return string Whom this window is sent to.
	 */
	public function getRecipient()
	{
		return $this->login;
	}
	
	/**
	 * @return bool Whether the Window is currently displayed on the screen.
	 */
	public function isShown()
	{
		return !$this->isHidden;
	}
	
	/**
	 * @return integer The maximum z-position that is used by this window.
	 */
	public function getMaxZ()
	{
		if (!$this->view) return Z_MIN;
		return $this->view->getMaxZ();
	}
	
	/**
	 * @return integer Windowing system identifier of that window.
	 */
	public function getId()
	{
		return $this->id;
	}
	
	/**
	 * @return integer Unique identifier for that window.
	 */
	function getUid()
	{
		return $this->uid;
	}
	
	/**
	 * Removes callbacks that have been created by
	 * a specific control.
	 * @param mixed $component
	 */
	public function removeCallbacks($component)
	{
		foreach ($this->callbacks as $action => $callback)
		{
			if ($callback[0][0] === $component)
			{
				unset($this->callbacks[$action]);
			}
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLib/Gui/ManiaLib\Gui.Component::resize()
	 */
	function resize($oldX, $oldY)
	{
		$this->onResize();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLib/Gui/ManiaLib\Gui.Component::move()
	 */
	function move($oldX, $oldY, $oldZ)
	{
		$this->onMove();
	}
	
	/**
	 * Window is being moved on the screen.
	 * Override this method to execute your
	 * own code on this event.
	 */
	function onMove() {}
	
	/**
	 * Window is resized.
	 * Override this method to execute your
	 * own code on this event.
	 */
	function onResize() {}
	
	/**
	 * Remove all references from this Window.
	 * Unregister events and remove references to this Window from components.
	 */
	public function destroy()
	{
//		echo "< unloading " . get_class($this) . "\n";
		
		Dispatcher::dispatch(new \ManiaLive\Gui\Windowing\Event($this,
			\ManiaLive\Gui\Windowing\Event::ON_WINDOW_CLOSE, $this->login));
		
		// we're closing ..
		if (!$this->isHidden)
		{
			$this->onHide();
		}
		
		// remove events	
		Dispatcher::unregister(\ManiaLive\Gui\Handler\Event::getClass(), $this);
		Dispatcher::unregister(Event::getClass(), $this);
		
		foreach ($this->below as $below)
		{
			unset($below->above[$this->uid]);
		}
		
		foreach ($this->above as $above)
		{
			unset($above->below[$this->uid]);
		}
		
		// remove references to windows below
		$this->below = array();
		$this->above = array();
		unset($this->playerValues[$this->getRecipient()]);
		$this->header = null;
		$this->closeCallbacks = array();
		
		// remove callbacks
		$this->callbacks = array();
		
		// remove reference to view
		if ($this->view)
		{
			$this->view->destroy();
		}
		$this->view = null;
		
		// finally remove actions from window
		IDGenerator::freeManialinkIDs($this->id);
		
		$this->clearComponents();
		
		// remove components
		foreach ($this->components as $component)
		{
			if ($component instanceof \ManiaLive\Gui\Windowing\Control)
			{
				$component->destroy();
			}
		}
		
		// remove from intern window list ...
		$class_name = get_called_class();
		unset(self::$instances[$this->login][$class_name]);
		unset(self::$instancesNonSingleton[$this->login][$this->uid]);
		unset(self::$instancesByClass[$class_name][$this->login][$this->uid]);
	}
	
	function __destruct()
	{
//		echo "<< desctructing " . get_class($this) . "\n";
	}
}

?>