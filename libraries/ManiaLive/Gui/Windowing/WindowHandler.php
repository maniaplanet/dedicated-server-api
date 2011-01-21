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

use ManiaLive\Gui\Windowing\Windows\Info;

use ManiaLive\Gui\Windowing\Windows\Thumbnail;

use ManiaLive\Gui\Displayables\Blank;
use ManiaLive\Gui\Handler\GuiHandler;
use ManiaLive\Utilities\Logger;
use ManiaLive\Data\Storage;
use ManiaLive\Event\Dispatcher;

/**
 * Sort windows to right order for being displayed on the screen.
 * Windows that are most bottom on the z-axis will be drawn first.
 *  
 * @author Florian Schnell
 */
class WindowHandler
	extends \ManiaLive\Utilities\Singleton
	implements \ManiaLive\Application\Listener,
	\ManiaLive\DedicatedApi\Callback\Listener
{
	protected $drawstackCount;
	protected $drawstack;
	protected $uptodate;
	protected $storage;
	
	protected $currentManagedWindow;
	protected $minimizedManagedWindows;
	protected $minimizedManagedWindowHashes;
	
	/**
	 * @var array[\ManiaLive\Gui\Windowing\Window]
	 */
	public static $dialog = array();
	
	/**
	 * Initialize on first use.
	 */
	function __construct()
	{
		$this->currentManagedWindow = array();
		$this->minimizedManagedWindows = array();
		$this->storage = Storage::getInstance();
		Dispatcher::register(\ManiaLive\Application\Event::getClass(), $this);
		Dispatcher::register(\ManiaLive\DedicatedApi\Callback\Event::getClass(), $this);
	}
	
	/**
	 * Draws the Windows, that have been modified this loop, in correct order.
	 * @see libraries/ManiaLive/Application/ManiaLive\Application.Listener::onPostLoop()
	 */
	function onPreLoop()
	{
		if ($this->drawstackCount == 0) 
		{
			return;
		}
		
		foreach ($this->drawstack as $login => $stack)
		{
			// if player has left the server
			if ($this->storage->getPlayerObject($login) == null)
			{
				continue; // then we don't need to draw anything!
			}
			
			// prepare window order ...
			$finalstack = array();
			foreach ($stack as $window)
			{
				$this->addToStack($finalstack, $window);
			}
			
			// render windows according to that order ...
			foreach ($finalstack as $window)
			{
				$window->render($login);
			}
		}
		
		$this->drawstack = array();
		$this->drawstackCount = 0;
	}
	
	/**
	 * Adds Windows in correct order to the drawing stack.
	 * @param array $stack
	 * @param Window $window
	 */
	function addToStack(&$stack, Window $window)
	{
		// fix: no window below caused exception
		// probably caused by a leaving player just when this window
		// for him is being sent.
		// the window handler then tries to render a partialy dropped window.
		if (is_array($window->below))
		{
			foreach ($window->below as $below)
			{
				$this->addToStack($stack, $below);
			}
			
			if (!$window->uptodate)
			{
				$stack[] = $window;
				$window->uptodate = true;
			}
		}
		else
		{
			Logger::getLog('Info')->write('Accessing window->below when it is not an array!');
		}
	}
	
	/**
	 * Put a Window to the queue for being displayed on the screen.
	 * @param Window $window
	 * @param string $login
	 */
	function add(Window $window, $login)
	{
		$this->drawstack[$login][] = $window;
		$this->drawstackCount++;
		
		return true;
	}
	
	/**
	 * @param Window $window
	 * @param unknown_type $login
	 */
	function addManaged(ManagedWindow $window, $login)
	{
		// if window gets removed from screen
		if (!$window->isShown())
		{
			if (isset($this->currentManagedWindow[$login])
				&& $window == $this->currentManagedWindow[$login])
			{
				// then also remove it from intern lists
				$this->currentManagedWindow[$login] = null;
				unset($this->minimizedManagedWindowHashes[$login][spl_object_hash($window)]);
			}
			
			// just overwrite current window with empty manialink
			$this->drawstack[$login][] = $window;
			$this->drawstackCount++;
			return true;
		}
		
		// managed window are always centerd on the screen!
		$window->centerOnScreen();
		
		// if window is being displayed already
		if (isset($this->currentManagedWindow[$login])
			&& $this->currentManagedWindow[$login] == $window)
		{
			// just redraw the window
			$this->drawstack[$login][] = $window;
			$this->drawstackCount++;
			return true;
		}
		
		// if window is currently minimized
		if (isset($this->minimizedManagedWindowHashes[$login][spl_object_hash($window)]))
		{
			// then search for it in the minimized window list
			for ($i = 0; $i < count($this->minimizedManagedWindows[$login]); $i++)
			{
				if ($this->minimizedManagedWindows[$login][$i]['window'] == $window)
				{
					// and if found remove it from the intern list
					// also remove thumbnail from the screen.
					$this->minimizedManagedWindows[$login][$i]['thumb']->hide();
					$this->minimizedManagedWindows[$login][$i] = null;
				}
			}
			
			unset($this->minimizedManagedWindowHashes[$login][spl_object_hash($window)]);
		}
		
		// if there is no currently opened managed window
		if (!isset($this->currentManagedWindow[$login])
			|| $this->currentManagedWindow[$login] == null)
		{
			// set this window as current and also add it
			// to the intern list.
			$this->currentManagedWindow[$login] = $window;
			$this->minimizedManagedWindowHashes[$login][spl_object_hash($window)] = true;
			
			// just redraw the window
			$this->drawstack[$login][] = $window;
			$this->drawstackCount++;
		}
		
		// if there currently is a window open
		// we need to attach it to the taskbar
		else
		{
			$oldWindow = $this->currentManagedWindow[$login];
			if (count($this->minimizedManagedWindowHashes[$login]) > 5)
			{
				$info = Info::Create($login);
				$info->setSize(40, 25);
				$info->setTitle('Too many Windows!');
				$info->setText("You are in the process of opening another window ...\n" .
					"Due to restricted resources you have reached the limit of allowed concurrent displayable windows.\n" .
					"Please close some old windows in order to be able to open now ones.");
				$oldWindow->showDialog($info);
				return false;
			}
			
			// swap the currently active with the new one.
			$oldWindow = $this->currentManagedWindow[$login];
			$this->currentManagedWindow[$login] = $window;
			
			// hide the old window
			$oldWindow->hide();
			
			// and create thumbnail from it
			$thumb = Thumbnail::fromWindow($oldWindow);
			
			$task = array(
				'thumb' => $thumb,
				'window' => $oldWindow
			);
			
			// if this is the first minimized window, then we just
			// put it to the first position.
			$i = 0;
			if (!isset($this->minimizedManagedWindows[$login]))
			{
				$this->minimizedManagedWindows[$login] = array($task);
			}
			else
			{
				// try to put thumbnail into an empty slot
				for (; $i < count($this->minimizedManagedWindows[$login]); $i++)
				{
					if ($this->minimizedManagedWindows[$login][$i] == null)
					{
						$this->minimizedManagedWindows[$login][$i] = $task;
						break;
					}
				}
				
				// if we've reached the end, then we will need to create a new slot.
				if ($i == count($this->minimizedManagedWindows[$login]))
				{
					$this->minimizedManagedWindows[$login][] = $task;
				}
			}
			
			// create hash entry for that new window.
			$this->minimizedManagedWindowHashes[$login][spl_object_hash($window)] = true;
		
			// display the thumbnail of the old window
			$thumb->setCloseCallback(array($this, 'onThumbClosed'));
			$thumb->setSize(20, 14);
			$thumb->setPosition(22 - 21 * $i, -47);
			$thumb->show();
			
			// move the new window above all the thumbnails
			foreach ($this->minimizedManagedWindows[$login] as $task)
			{
				if ($task)
					$window->moveAbove($task['thumb']);
			}
			
			// add the new window to the drawing stack!
			$this->drawstack[$login][] = $window;
			$this->drawstackCount ++;
		}
		
		return true;
	}
	
	/**
	 * This method will be called when a thumbnail has been
	 * closed.
	 */
	function onThumbClosed($login, Window $thumb)
	{
		$windows = $this->minimizedManagedWindows[$login];
		$windowsCount = count($windows);
		for ($i = 0; $i < $windowsCount; $i++)
		{
			if ($windows[$i]['thumb'] == $thumb)
			{
				unset($this->minimizedManagedWindowHashes[$login][spl_object_hash($windows[$i]['window'])]);
				$windows[$i] = null;
			}
		}
		$this->minimizedManagedWindows[$login] = $windows;
		
		foreach (Window::$instancesNonSingleton[$login] as $i => $win)
		{
			if (Window::$instancesNonSingleton[$login][$i] == $thumb)
			{
				unset(Window::$instancesNonSingleton[$login][$i]);
			}
		}
		
		$thumb->destroy();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/DedicatedApi/Callback/ManiaLive\DedicatedApi\Callback.Listener::onPlayerDisconnect()
	 */
	function onPlayerDisconnect($login)
	{
		// clear drawstack for that player
		unset($this->drawstack[$login]);
		
		// free the dialog
		unset(self::$dialog[$login]);
		
		// free managed windows ...
		unset($this->currentManagedWindow[$login]);
		unset($this->minimizedManagedWindowHashes[$login]);
		unset($this->minimizedManagedWindows[$login]);
		
		// clean memory ...
		self::destroyPlayerWindows($login);
		gc_collect_cycles();

//		echo "Controls that have not been freed:\n";
//		foreach (Control::$controls as $control)
//		{
//			echo "- " . $control . "\n";
//		}
	}
	
	/**
	 * Removes all window resources that have been allocated
	 * for the player.
	 * @param string $login Players Login
	 */
	static function destroyPlayerWindows($login)
	{
//		echo "\nremoving player windows!\n";
		
		if (isset(Window::$instances[$login]))
		{
			foreach (Window::$instances[$login] as $window)
			{
				$window->destroy();
			}
			unset(Window::$instances[$login]);
		}
		
		if (isset(Window::$instancesNonSingleton[$login]))
		{
			foreach (Window::$instancesNonSingleton[$login] as $window)
			{
				$window->destroy();
			}
			unset(Window::$instancesNonSingleton[$login]);
		}
	}
	
	/**
	 * Change the UI that is displayed in the game.
	 * You can either change it for a single player, for a group of
	 * players, or for everyone.
	 * @param \ManiaLive\Gui\Windowing\CustomUI $customUI
	 * @param array[\ManiaLive\DedicatedApi\Structures\Player] $players
	 */
	static function setCustomUI(CustomUI $customUI, $players = null)
	{
		$guihandler = GuiHandler::getInstance();
		
		$group = $guihandler->getGroup($players);
		
		$group->displayableGroup->showCustomUi = true;
		$group->displayableGroup->chat = $customUI->chat;
		$group->displayableGroup->checkpointList = $customUI->checkpointList;
		$group->displayableGroup->challengeInfo = $customUI->challengeInfo;
		$group->displayableGroup->global = $customUI->global;
		$group->displayableGroup->notice = $customUI->notice;
		$group->displayableGroup->scoretable = $customUI->scoretable;
		$group->displayableGroup->roundScores = $customUI->roundScores;
	}
	
	function closeWindowThumbs($class_name)
	{
		foreach ($this->minimizedManagedWindows as $login => &$tasks)
		{
			foreach ($tasks as &$task)
			{
				if (get_class($task['window']) == $class_name)
				{
					unset($this->minimizedManagedWindowHashes[$login][spl_object_hash($task['window'])]);
					$task['thumb']->hide();
					$task['thumb']->setCloseCallback(array($task['thumb'], 'destroy'));
					$task = null;
				}
			}
		}
	}
	
	function onInit() {}
	function onPostLoop() {}
	function onTerminate() {}
	function onRun() {}
	
	function onBeginChallenge($challenge, $warmUp, $matchContinuation) {}
	function onBeginRace($challenge) {}
	function onBeginRound() {}
	function onBillUpdated($billId, $state, $stateName, $transactionId) {}
	function onChallengeListModified($curChallengeIndex, $nextChallengeIndex, $isListModified) {}
	function onEcho($internal, $public) {}
	function onEndChallenge($rankings, $challenge, $wasWarmUp, $matchContinuesOnNextChallenge, $restartChallenge) {}
	function onEndRace($rankings, $challenge) {}
	function onEndRound() {}
	function onManualFlowControlTransition($transition) {}
	function onPlayerChat($playerUid, $login, $text, $isRegistredCmd) {}
	function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex) {}
	function onPlayerConnect($login, $isSpectator) {}
	function onPlayerFinish($playerUid, $login, $timeOrScore) {}
	function onPlayerIncoherence($playerUid, $login) {}
	function onPlayerInfoChanged($playerInfo) {}
	function onPlayerManialinkPageAnswer($playerUid, $login, $answer) {}
	function onServerStart() {}
	function onServerStop() {}
	function onStatusChanged($statusCode, $statusName) {}
	function onTunnelDataReceived($playerUid, $login, $data) {}
}

?>