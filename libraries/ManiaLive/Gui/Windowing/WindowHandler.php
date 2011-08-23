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

use ManiaLive\Gui\Windowing\Windows\Taskbar;
use ManiaLive\DedicatedApi\Connection;
use ManiaLive\DedicatedApi\Structures\Player;
use ManiaLive\Gui\Windowing\Windows\Shortkey;
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
 * @author Florian Schnell
 */
class WindowHandler extends \ManiaLib\Utils\Singleton implements \ManiaLive\Application\Listener, \ManiaLive\DedicatedApi\Callback\Listener
{

    protected $storage;
    protected static $drawStackCount = 0;
    protected static $drawStack = array();
    protected static $finalStack = array();
    protected static $dialogStack = array();
    public static $dialogRefreshed = array();
    protected static $maximized = array();
    protected static $playerHiddenWindows = array();
    protected static $currentManagedWindow = array();
    protected static $minimizedManagedWindows = array();
    protected static $minimizedManagedWindowHashes = array();

    /**
	* How many windows can the taskbar take?
	* @var integer
	*/
    const MAX_MINIMIZEABLE = 5;

    /**
	* Initialize on first use.
	*/
    function __construct()
    {
	   $this->storage = Storage::getInstance();
	   Dispatcher::register(\ManiaLive\Application\Event::getClass(), $this);
	   Dispatcher::register(\ManiaLive\DedicatedApi\Callback\Event::getClass(), $this);
    }

    /**
	* @return \ManiaLive\Gui\Windowing\WindowHandler
	*/
    static function getInstance()
    {
	   return parent::getInstance();
    }

    static function showHideInterface($login)
    {
	   $player = new Player();
	   $player->login = $login;

	   if(isset(self::$playerHiddenWindows[$login]))
	   {
		  $temp = self::$playerHiddenWindows[$login];
		  unset(self::$playerHiddenWindows[$login]);
		  foreach($temp as $window)
		  {
			 $window->show();
		  }
	   }
	   else
	   {
		  self::$playerHiddenWindows[$login] = array();
		  foreach(Window::Get($login) as $window)
		  {
			 if($window->isVisible() && !($window instanceof Shortkey))
			 {
				$group = GuiHandler::getInstance()->getGroup($player);
				$group->displayableGroup->addDisplayable(new Blank($window->getId()));
				self::$playerHiddenWindows[$login][] = $window;
			 }
		  }
		  Connection::getInstance()->chatSendServerMessage('ManiaLive interface has been deactivated, press F7 to enable ...', array($player), true);
	   }
    }

    /**
	* Draws the Windows, that have been modified this loop, in correct order.
	* @see libraries/ManiaLive/Application/ManiaLive\Application.Listener::onPostLoop()
	*/
    function onPreLoop()
    {
	   self::$finalStack = array();

	   if(self::$drawStackCount == 0)
	   {
		  return;
	   }

	   foreach(self::$drawStack as $login => &$stack)
	   {

		  // if window is addressed to everyone
		  if($login == Window::RECIPIENT_ALL)
		  {
			 // but there is no one on the server
			 if(!count($this->storage->players) && !count($this->storage->spectators))
			 {
				continue; // then we don't need to draw anything!
			 }
		  }
		  // if player has left the server
		  elseif($this->storage->getPlayerObject($login) == null)
		  {
			 continue; // then we don't need to draw anything!
		  }

		  // if there's a dialog shown currently
		  if(isset(self::$dialogStack[$login])
				&& end(self::$dialogStack[$login]))
		  {
			 // and it has been refreshed yet
			 if(isset(self::$dialogRefreshed[$login])
				    && self::$dialogRefreshed[$login])
			 {
				continue;
			 }
		  }

		  // don't draw if player's hiding interface
		  if(isset(self::$playerHiddenWindows[$login]))
		  {
			 continue;
		  }

		  // prepare window order ...
		  $drawMaximized = false;
		  self::$finalStack[$login] = array();
		  while($window = array_shift($stack))
		  {
			 if(isset(self::$maximized[$login]))
			 {
				if($window === self::$maximized[$login])
				{
				    $drawMaximized = true;
				    self::$drawStackCount--;
				    continue;
				}
				else
				{
				    self::$maximized[$login]->moveAbove($window);
				}
			 }

			 if(isset(self::$dialogStack[$login])
				    && ($dialog = end(self::$dialogStack[$login]))
				    && $dialog != $window)
			 {
				$dialog->moveAbove($window);
			 }
			 else
			 {
				self::$dialogRefreshed[$login] = true;
			 }

			 self::$drawStackCount--;
			 $this->addToStack(self::$finalStack[$login], $window);
		  }

		  // render windows according to that order ...
		  foreach(self::$finalStack[$login] as $window)
		  {
			 $window->render($login);
		  }

		  // draw the maximized window
		  if($drawMaximized)
		  {
			 self::$maximized[$login]->setPosZ(Window::GetTopZ($login, self::$maximized[$login]));
			 self::$maximized[$login]->render($login);
		  }
	   }
    }

    /**
	* Draws dialog windows on top of all the rest.
	* @see libraries/ManiaLive/Application/ManiaLive\Application.Listener::onPostLoop()
	*/
    function onPostLoop()
    {
	   foreach(self::$dialogStack as $login => $stack)
	   {
		  // if the dialog needs refreshing
		  if(isset(self::$dialogRefreshed[$login])
				&& !self::$dialogRefreshed[$login])
		  {
			 // and there is a dialog
			 $dialog = end($stack);
			 if($dialog !== false)
			 {
				// either hide or show it
				if($dialog->isVisible())
				{
				    $dialog->setPosZ(Window::GetTopZ($login));
				    $dialog->show();
				}
				else
				{
				    $dialog->render($login);
				}
			 }
		  }
	   }
    }

    /**
	* Show a window as dialog.
	* This means the window is on top of all other windows.
	* All other buttons are deactivated until the window is closed.
	* If there is more than one dialog shown at a time, the one that has
	* been created later will be displayed after the first one has been closed.
	* @param Window $dialog
	*/
    static function showDialog(Window $dialog)
    {
	   $login = $dialog->getRecipient();

	   if(!isset(self::$dialogStack[$login]))
		  self::$dialogStack[$login] = array();

	   $dialog->addCloseCallback(array(__NAMESPACE__.'\WindowHandler', 'onCloseDialog'));

	   array_unshift(self::$dialogStack[$login], $dialog);

	   $dialog->show();

	   self::$dialogRefreshed[$login] = false;
    }

    /**
	* Will return the thumbnail for a specific window
	* if it is currently minimized.
	* @param \ManiaLive\Gui\Windowing\ManagedWindow $window
	* @return \ManiaLive\Gui\Windowing\Windows\Thumbnail
	*/
    static function getThumbnail(ManagedWindow $window)
    {
	   $login = $window->getRecipient();
	   if(isset(self::$minimizedManagedWindowHashes[$login][spl_object_hash($window)]))
	   {
		  return self::$minimizedManagedWindowHashes[$login][spl_object_hash($window)]['thumb'];
	   }
	   else
	   {
		  return null;
	   }
    }

    /**
	* Returns the window that currently is maximized.
	* If there's currently no maximized window this will return false.
	* @param $login
	*/
    static function getMaximized($login)
    {
	   if(!isset(self::$maximized[$login]))
		  return false;

	   return self::$maximized[$login];
    }

    /**
	* Gets the current dialog that is displayed,
	* if there's no dialog this method will return false.
	* @param $login
	*/
    static function getDialog($login)
    {
	   if(!isset(self::$dialogStack[$login]))
		  return false;

	   if(end(self::$dialogStack[$login]))
	   {
		  return end(self::$dialogStack[$login]);
	   }
	   else
	   {
		  return false;
	   }
    }

    /**
	* Close dialog.
	* @param unknown_type $login
	* @param unknown_type $window
	*/
    static function onCloseDialog($login, $window)
    {
	   if(isset(self::$dialogStack[$login]))
	   {
		  array_pop(self::$dialogStack[$login]);
	   }
	   self::$dialogRefreshed[$login] = true;
	   $window->destroy();
    }

    /**
	* Adds Windows in correct order to the drawing stack.
	* @param array $stack
	* @param Window $window
	*/
    protected static function addToStack(&$stack, Window $window)
    {
	   // fix: no window below caused exception
	   // probably caused by a leaving player just when this window
	   // for him is being sent.
	   // the window handler then tries to render a partialy dropped window.
	   if(is_array($window->below))
	   {
		  foreach($window->below as $below)
		  {
			 self::addToStack($stack, $below);
		  }

		  if(!$window->uptodate)
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
    static function add(Window $window, $login)
    {
	   self::$drawStack[$login][] = $window;
	   self::$drawStackCount++;

	   return true;
    }

    /**
	* Adds a new Window to the handler, which will
	* take care of the displaying.
	* @param Window $window
	* @param unknown_type $login
	*/
    static function addManaged(ManagedWindow $window, $login)
    {
	   if(isset(self::$playerHiddenWindows[$login]))
	   {
		  return;
	   }

	   // if window gets removed from screen
	   if(!$window->isVisible())
	   {
		  if(isset(self::$currentManagedWindow[$login])
				&& $window == self::$currentManagedWindow[$login])
		  {
			 // then also remove it from intern lists
			 self::$currentManagedWindow[$login] = null;
			 unset(self::$minimizedManagedWindowHashes[$login][spl_object_hash($window)]);
		  }

		  if(isset(self::$maximized[$login])
				&& self::$maximized[$login] === $window)
		  {
			 unset(self::$maximized[$login]);
		  }

		  // just overwrite current window with empty manialink
		  self::$drawStack[$login][] = $window;
		  self::$drawStackCount++;
		  return true;
	   }

	   // if window is being displayed already
	   if(isset(self::$currentManagedWindow[$login])
			 && self::$currentManagedWindow[$login] == $window)
	   {
		  if($window->isMaximized())
		  {
			 if(!isset(self::$maximized[$login]))
			 {
				self::$maximized[$login] = $window;
			 }
		  }
		  else
		  {
			 if(isset(self::$maximized[$login])
				    && self::$maximized[$login] === $window)
			 {
				unset(self::$maximized[$login]);
			 }
		  }

		  // just redraw the window
		  self::$drawStack[$login][] = $window;
		  self::$drawStackCount++;
		  return true;
	   }

	   // if window is currently minimized
	   if(isset(self::$minimizedManagedWindowHashes[$login][spl_object_hash($window)]))
	   {
		  // then search for it in the minimized window list
		  for($i = 0; $i < count(self::$minimizedManagedWindows[$login]); $i++)
		  {
			 if(self::$minimizedManagedWindows[$login][$i]['window'] == $window)
			 {
				// and if found remove it from the intern list
				// also remove thumbnail from the screen.
				self::$minimizedManagedWindows[$login][$i]['thumb']->hide();
				self::$minimizedManagedWindows[$login][$i] = null;
			 }
		  }

		  unset(self::$minimizedManagedWindowHashes[$login][spl_object_hash($window)]);
	   }

	   // if there is no currently opened managed window
	   if(!isset(self::$currentManagedWindow[$login])
			 || self::$currentManagedWindow[$login] == null)
	   {
		  // set this window as current and also add it
		  // to the intern list.
		  self::$currentManagedWindow[$login] = $window;

		  // just redraw the window
		  self::$drawStack[$login][] = $window;
		  self::$drawStackCount++;
	   }

	   // if there currently is a window open
	   // we need to attach it to the taskbar
	   else
	   {
		  if(!self::sendCurrentWindowToTaskbar($login))
		  {
			 return false;
		  }

		  // set the new managed window
		  self::$currentManagedWindow[$login] = $window;

		  if(self::$currentManagedWindow)

		  // move the new window above all the thumbnails
			 foreach(self::$minimizedManagedWindows[$login] as $task)
			 {
				if($task)
				    $window->moveAbove($task['thumb']);
			 }

		  // add the new window to the drawing stack!
		  self::$drawStack[$login][] = $window;
		  self::$drawStackCount++;
	   }

	   return true;
    }

    /**
	* Takes the window that is currently shown on the screen
	* and puts it into the taskbar.
	* @param unknown_type $login
	*/
    static function sendCurrentWindowToTaskbar($login)
    {
	   $oldWindow = self::$currentManagedWindow[$login];
	   if(isset(self::$minimizedManagedWindowHashes[$login])
			 && count(self::$minimizedManagedWindowHashes[$login]) >= self::MAX_MINIMIZEABLE)
	   {
		  $info = Info::Create($login);
		  $info->setSize(40, 25);
		  $info->setTitle('Too many Windows!');
		  $info->setText("You are in the process of minimizing another window ...\n".
				"Due to restricted resources you have reached the limit of allowed concurrent displayable minimized windows.\n".
				"Please close some old windows in order to be able to open and minimize new ones.");
		  $oldWindow->showDialog($info);
		  return false;
	   }

	   // swap the currently active with the new one.
	   $oldWindow = self::$currentManagedWindow[$login];
	   self::$currentManagedWindow[$login] = null;

	   // hide the old window
	   if(empty($oldWindow) || $oldWindow == null)
	   {
		  return false;
	   }

	   $oldWindow->hide();

	   // and create thumbnail from it
	   $thumb = Thumbnail::fromWindow($oldWindow);

	   $task = array(
		  'thumb' => $thumb,
		  'window' => $oldWindow
	   );

	   // this window is now minimized
	   self::$minimizedManagedWindowHashes[$login][spl_object_hash($oldWindow)] = $task;

	   // if this is the first minimized window, then we just
	   // put it to the first position.
	   $i = 0;
	   if(!isset(self::$minimizedManagedWindows[$login]))
	   {
		  self::$minimizedManagedWindows[$login] = array($task);
	   }
	   else
	   {
		  // try to put thumbnail into an empty slot
		  for(; $i < count(self::$minimizedManagedWindows[$login]); $i++)
		  {
			 if(self::$minimizedManagedWindows[$login][$i] == null)
			 {
				self::$minimizedManagedWindows[$login][$i] = $task;
				break;
			 }
		  }

		  // if we've reached the end, then we will need to create a new slot.
		  if($i == count(self::$minimizedManagedWindows[$login]))
		  {
			 self::$minimizedManagedWindows[$login][] = $task;
		  }
	   }

	   // display the thumbnail of the old window
	   $thumb->setCloseCallback(array(__NAMESPACE__.'\WindowHandler', 'onThumbClosed'));
	   $thumb->setSize(20, 14);
	   $thumb->setPosition(22 - 21 * $i, -47);
	   $thumb->show();

	   return true;
    }

    /**
	* This method will be called when a thumbnail has been
	* closed.
	*/
    static function onThumbClosed($login, Window $thumb)
    {
	   $windows = self::$minimizedManagedWindows[$login];
	   $windowsCount = count($windows);
	   for($i = 0; $i < $windowsCount; $i++)
	   {
		  if($windows[$i]['thumb'] === $thumb)
		  {
			 unset(self::$minimizedManagedWindowHashes[$login][spl_object_hash($windows[$i]['window'])]);
			 $windows[$i] = null;
		  }
	   }
	   self::$minimizedManagedWindows[$login] = $windows;

	   foreach(Window::$instancesNonSingleton[$login] as $i => $win)
	   {
		  if(Window::$instancesNonSingleton[$login][$i] === $thumb)
		  {
			 unset(Window::$instancesNonSingleton[$login][$i]);
		  }
	   }

	   $thumb->destroy();
    }

    /**
	* Open the last available window from the taskbar.
	* @param string $login
	*/
    static function showNextWindow($login)
    {
	   if(!isset(self::$minimizedManagedWindows[$login]))
		  return;
	   for($i = 0; $i < count(self::$minimizedManagedWindows[$login]); $i++)
	   {
		  if(self::$minimizedManagedWindows[$login][$i] !== null)
		  {
			 self::$minimizedManagedWindows[$login][$i]['window']->show();
		  }
	   }
    }

    /**
	* Open the first available window from the taskbar.
	* @param string $login
	*/
    static function showPreviousWindow($login)
    {
	   if(!isset(self::$minimizedManagedWindows[$login]))
		  return;
	   for($i = count(self::$minimizedManagedWindows[$login]) - 1; $i >= 0; --$i)
	   {
		  if(self::$minimizedManagedWindows[$login][$i] !== null)
		  {
			 self::$minimizedManagedWindows[$login][$i]['window']->show();
		  }
	   }
    }

    /**
	* (non-PHPdoc)
	* @see libraries/ManiaLive/Application/ManiaLive\Application.Listener::onInit()
	*/
    function onInit()
    {
	   foreach($this->storage->players as $login => $player)
	   {
		  $sk = Shortkey::Create($login);
		  $sk->addCallback(Shortkey::F7, array('\ManiaLive\Gui\Windowing\WindowHandler', 'showHideInterface'));
		  $sk->addCallback(Shortkey::F6, array('\ManiaLive\Gui\Windowing\WindowHandler', 'showPreviousWindow'));
		  $sk->addCallback(Shortkey::F5, array('\ManiaLive\Gui\Windowing\WindowHandler', 'showNextWindow'));
		  $sk->show();
	   }

	   foreach($this->storage->spectators as $login => $spectator)
	   {
		  $sk = Shortkey::Create($login);
		  $sk->addCallback(Shortkey::F7, array('\ManiaLive\Gui\Windowing\WindowHandler', 'showHideInterface'));
		  $sk->addCallback(Shortkey::F6, array('\ManiaLive\Gui\Windowing\WindowHandler', 'showPreviousWindow'));
		  $sk->addCallback(Shortkey::F5, array('\ManiaLive\Gui\Windowing\WindowHandler', 'showNextWindow'));
		  $sk->show();
	   }
    }

    /**
	* (non-PHPdoc)
	* @see libraries/ManiaLive/DedicatedApi/Callback/ManiaLive\DedicatedApi\Callback.Listener::onPlayerConnect()
	*/
    function onPlayerConnect($login, $IsSpectator)
    {
	   $sk = Shortkey::Create($login);
	   $sk->addCallback(Shortkey::F7, array('\ManiaLive\Gui\Windowing\WindowHandler', 'showHideInterface'));
	   $sk->addCallback(Shortkey::F6, array('\ManiaLive\Gui\Windowing\WindowHandler', 'showPreviousWindow'));
	   $sk->addCallback(Shortkey::F5, array('\ManiaLive\Gui\Windowing\WindowHandler', 'showNextWindow'));
	   $sk->show();
    }

    /**
	* (non-PHPdoc)
	* @see libraries/ManiaLive/DedicatedApi/Callback/ManiaLive\DedicatedApi\Callback.Listener::onPlayerDisconnect()
	*/
    function onPlayerDisconnect($login)
    {
	   // clear drawStack for that player
	   unset(self::$drawStack[$login]);

	   // free the dialog
	   self::$dialogStack = array();

	   // free managed windows ...
	   unset(self::$currentManagedWindow[$login]);
	   unset(self::$minimizedManagedWindowHashes[$login]);
	   unset(self::$minimizedManagedWindows[$login]);

	   // clean memory ...
	   self::destroyPlayerWindows($login);
	   gc_collect_cycles();
    }

    /**
	* Removes all window resources that have been allocated
	* for the player.
	* @param string $login Players Login
	*/
    static function destroyPlayerWindows($login)
    {

	   if(isset(Window::$instances[$login]))
	   {
		  foreach(Window::$instances[$login] as $window)
		  {
			 $window->destroy();
		  }
		  unset(Window::$instances[$login]);
	   }

	   if(isset(Window::$instancesNonSingleton[$login]))
	   {
		  foreach(Window::$instancesNonSingleton[$login] as $window)
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

    /**
	* Closes all window thumbs that have a specific
	* window class.
	* @param $class_name
	*/
    static function closeWindowThumbs($class_name)
    {
	   foreach(self::$minimizedManagedWindows as $login => &$tasks)
	   {
		  foreach($tasks as &$task)
		  {
			 if(get_class($task['window']) == $class_name)
			 {
				unset(self::$minimizedManagedWindowHashes[$login][spl_object_hash($task['window'])]);
				$task['thumb']->hide();
				$task['thumb']->setCloseCallback(array($task['thumb'], 'destroy'));
				$task = null;
			 }
		  }
	   }
    }

    function onTerminate()
    {

    }

    function onRun()
    {

    }

    function onBeginChallenge($challenge, $warmUp, $matchContinuation)
    {

    }

    function onBeginRace($challenge)
    {

    }

    function onBeginRound()
    {

    }

    function onBillUpdated($billId, $state, $stateName, $transactionId)
    {

    }

    function onChallengeListModified($curChallengeIndex, $nextChallengeIndex, $isListModified)
    {

    }

    function onEcho($internal, $public)
    {

    }

    function onEndChallenge($rankings, $challenge, $wasWarmUp, $matchContinuesOnNextChallenge, $restartChallenge)
    {

    }

    function onEndRace($rankings, $challenge)
    {

    }

    function onEndRound()
    {

    }

    function onManualFlowControlTransition($transition)
    {

    }

    function onPlayerChat($playerUid, $login, $text, $isRegistredCmd)
    {

    }

    function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex)
    {

    }

    function onPlayerFinish($playerUid, $login, $timeOrScore)
    {

    }

    function onPlayerIncoherence($playerUid, $login)
    {

    }

    function onPlayerInfoChanged($playerInfo)
    {

    }

    function onPlayerManialinkPageAnswer($playerUid, $login, $answer, array $entries)
    {

    }

    function onServerStart()
    {

    }

    function onServerStop()
    {

    }

    function onStatusChanged($statusCode, $statusName)
    {

    }

    function onTunnelDataReceived($playerUid, $login, $data)
    {

    }

    function onVoteUpdated($stateName, $login, $cmdName, $cmdParam)
    {

    }
	
	function onRulesScriptCallback($param1, $param2)
	{
		
	}
}

?>