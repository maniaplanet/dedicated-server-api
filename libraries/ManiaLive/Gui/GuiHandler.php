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

use ManiaLib\Gui\Elements\Quad;
use ManiaLive\Event\Dispatcher;
use ManiaLive\Application\Listener as AppListener;
use ManiaLive\Application\Event as AppEvent;
use ManiaLive\DedicatedApi\Callback\Listener as ServerListener;
use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;
use ManiaLive\DedicatedApi\Connection;
use ManiaLive\DedicatedApi\Structures\Player;
use ManiaLive\Data\Storage;
use ManiaLive\Gui\Controls\Frame;
use ManiaLive\Gui\Windows\Info;
use ManiaLive\Gui\Windows\Shortkey;
use ManiaLive\Gui\Windows\Thumbnail;

/**
 * Description of GuiHandler
 */
final class GuiHandler extends \ManiaLib\Utils\Singleton implements AppListener, ServerListener
{
	const MAX_THUMBNAILS = 5;
	const NEXT_IS_DIALOG = 0x15D1A106;
	
	private $hidingGui = array();
	private $dialogs = array();
	private $dialogShown = array();
	private $managedWindow = array();
	private $thumbnails = array();
	
	private $currentWindows = array();
	private $nextWindows = array();
	private $dialogBg;
	
	private $nextLoop;
	
	// Profiling
	private $sendingTimes = array();
	private $averageSendingTimes;
	
	protected function __construct()
	{
		$this->dialogBg = new Quad(340, 200);
		$this->dialogBg->setBgColor('234C');
		$this->dialogBg->setAlign('center', 'center');
		$this->dialogBg->setPosZ(Window::Z_DIALOG);
		$this->dialogBg->setScriptEvents();
		$this->nextLoop = microtime(true);
		Dispatcher::register(AppEvent::getClass(), $this, AppEvent::ON_RUN | AppEvent::ON_PRE_LOOP);
		Dispatcher::register(ServerEvent::getClass(), $this, ServerEvent::ON_PLAYER_CONNECT | ServerEvent::ON_PLAYER_DISCONNECT);
	}
	
	function getAverageSendingTimes()
	{
		return $this->averageSendingTimes;
	}
	
	function toggleGui($login)
	{
		$this->hidingGui[$login] = !$this->hidingGui[$login];
		if($this->hidingGui[$login])
		{
			$connection = Connection::getInstance();
			$connection->chatSendServerMessage('ManiaLive interface has been deactivated, press F8 to enable...', $login, true);
			$connection->sendHideManialinkPage($login, true);
			Manialinks::load();
			$this->drawWindow(Shortkey::Create($login));
			CustomUI::Create($login)->saveToDefault();
			$connection->sendDisplayManialinkPage($login, Manialinks::getXml(), 0, false, true);
			$connection->executeMulticall();
		}
		else
		{
			Manialinks::load();
			foreach($this->currentWindows as $visibilityByLogin)
				if(isset($visibilityByLogin[$login]))
					$this->drawWindow($visibilityByLogin[$login]);
			if($this->dialogShown[$login])
				$this->drawDialog($this->dialogShown[$login]);
			$this->drawWindow(Shortkey::Create($login));
			CustomUI::Create($login)->save();
			$connection = Connection::getInstance();
			$connection->sendDisplayManialinkPage($login, Manialinks::getXml(), 0, false);
		}
	}
	
	function addtoShow(Window $window, $logins)
	{
		if($window instanceof ManagedWindow)
		{
			if($this->managedWindow[$logins] && $this->managedWindow[$logins] !== $window && !$this->sendToTaskbar($logins))
				return false;
			$this->managedWindow[$logins] = $window;
			if(($thumbnail = $this->getThumbnail($window)))
				$thumbnail->hide();
		}
		
		if(!is_array($logins))
			$logins = array($logins);
		
		foreach($logins as $login)
		{
			if(isset($this->nextWindows[$window->getId()]))
				$this->nextWindows[$window->getId()][$login] = $window;
			else
				$this->nextWindows[$window->getId()] = array($login => $window);
		}
		
		return true;
	}
	
	function addToHide(Window $window, $logins)
	{
		if($window instanceof ManagedWindow && $this->managedWindow[$logins] === $window)
			$this->managedWindow[$logins] = null;
		
		if(isset($this->currentWindows[$window->getId()]))
		{
			if(!is_array($logins))
				$logins = array($logins);
			
			foreach($logins as $login)
			{
				if(isset($this->currentWindows[$window->getId()][$login]))
				{
					if(isset($this->nextWindows[$window->getId()]))
						$this->nextWindows[$window->getId()][$login] = false;
					else
						$this->nextWindows[$window->getId()] = array($login => false);
				}
				else
				{
					unset($this->nextWindows[$window->getId()][$login]);
					if(!$this->nextWindows[$window->getId()])
						unset($this->nextWindows[$window->getId()]);
				}
			}
		}
		else if($window === $this->dialogShown[$logins])
		{
			if(isset($this->nextWindows[$window->getId()]))
				$this->nextWindows[$window->getId()][$logins] = false;
			else
				$this->nextWindows[$window->getId()] = array($logins => false);
		}
		else
			unset($this->nextWindows[$window->getId()]);
		
		return true;
	}
	
	function addToRedraw(Window $window, $login = null)
	{
		if($window instanceof ManagedWindow && ($thumbnail = $this->getThumbnail($window)))
			$thumbnail->enableHighlight();
		else if(isset($this->currentWindows[$window->getId()]))
		{
			if(isset($this->currentWindows[$window->getId()][$login]))
			{
				if(isset($this->nextWindows[$window->getId()]))
				{
					if(!isset($this->nextWindows[$window->getId()][$login]))
						$this->nextWindows[$window->getId()][$login] = $window;
				}
				else
					$this->nextWindows[$window->getId()] = array($login => $window);
			}
		}
		
		return true;
	}
	
	function sendToTaskbar($login)
	{
		$window = $this->managedWindow[$login];
		// seeking an empty place in the player taskbar
		$taskbarIndex = 0;
		$freePlaceFound = false;
		foreach($this->thumbnails[$login] as $taskbarIndex => $placedThumbnail)
			if(!$placedThumbnail)
			{
				$freePlaceFound = true;
				break;
			}
		if(!$freePlaceFound)
		{
			if($taskbarIndex == self::MAX_THUMBNAILS - 1)
			{
				$info = Info::Create($login, false);
				$info->setSize(40, 25);
				$info->setTitle('Too many Windows!');
				$info->setText("You are in the process of minimizing another window ...\n".
					"Due to restricted resources you have reached the limit of allowed concurrent displayable minimized windows.\n".
					"Please close some old windows in order to be able to open and minimize new ones.");
				$this->addDialog($info);
				return false;
			}
			else
				$taskbarIndex = count($this->thumbnails[$login]);
		}
		
		// create the thumbnail
		$thumbnail = Thumbnail::Create($login, false, $window);
		$this->thumbnails[$login][$taskbarIndex] = $thumbnail;
		$thumbnail->setSize(30, 26);
		$thumbnail->setPosition(80 - 31 * $taskbarIndex, 85);
		$thumbnail->addCloseCallback(array($this, 'onThumbnailClosed'));
		$thumbnail->show();
		$window->hide();
		$this->managedWindow[$login] = null;
		
		return true;
	}
	
	function onThumbnailClosed($login, Thumbnail $thumbnail)
	{
		$taskbarIndex = array_search($thumbnail, $this->thumbnails[$login], true);
		if($taskbarIndex !== false)
			$this->thumbnails[$login][$taskbarIndex] = false;
		$thumbnail->destroy();
	}
	
	private function getNextDialog($login)
	{
		if($this->dialogShown[$login])
			return null;
		return array_shift($this->dialogs[$login]);
	}
	
	function addDialog(Window $dialog)
	{
		$this->dialogs[$dialog->getRecipient()][] = $dialog;
		
		$dialog->addCloseCallback(array($this, 'onDialogClosed'));
	}
	
	function onDialogClosed($login, Window $dialog)
	{
		$dialog->destroy();
		$this->dialogShown[$login] = null;
	}
	
	function getThumbnail(ManagedWindow $window)
	{
		$login = $window->getRecipient();
		if(isset($this->thumbnails[$login]))
			foreach($this->thumbnails[$login] as $thumbnail)
				if($thumbnail && $thumbnail->getWindow() === $window)
					return $thumbnail;
		return null;
	}
	
	// Application Listener
	
	function onRun()
	{
		foreach(Storage::getInstance()->players as $login => $player)
			$this->onPlayerConnect($login, false);

		foreach(Storage::getInstance()->spectators as $login => $spectator)
			$this->onPlayerConnect($login, true);
	}
	
	function onInit() {}
	
	function onPreLoop()
	{
		// Before loops (stopping if too soon)
		$startTime = microtime(true);
		if($startTime < $this->nextLoop)
			return;
		
		$connection = Connection::getInstance();
		$stackByPlayer = array();
		$playersHidingGui = array_keys(array_filter($this->hidingGui));
		$playersShowingGui = array_diff(array_keys($this->hidingGui), $playersHidingGui);
		// First loop to prepare player stacks
		foreach($this->nextWindows as $windowId => $visibilityByLogin)
		{
			$showing = array_diff(array_keys(array_filter($visibilityByLogin)), $playersHidingGui);
			$hiding = array_diff(array_keys($visibilityByLogin), $showing, $playersHidingGui);
			if(count($showing))
			{
				sort($showing);
				$stackByPlayer[implode(',', $showing)][] = $visibilityByLogin[reset($showing)];
			}
			if(count($hiding))
			{
				sort($hiding);
				$stackByPlayer[implode(',', $hiding)][] = $windowId;
			}
		}
		// Second loop to add dialogs and regroup identical custom UIs
		$loginsByDiff = array();
		$customUIsByDiff = array();
		foreach($playersShowingGui as $login)
		{
			$dialog = $this->getNextDialog($login);
			if($dialog)
			{
				$stackByPlayer[$login][] = self::NEXT_IS_DIALOG;
				$stackByPlayer[$login][] = $dialog;
				$this->dialogShown[$login] = $dialog;
			}
			
			$customUI = CustomUI::Create($login);
			$diff = $customUI->getDiff();
			if($diff)
			{
				$loginsByDiff[$diff][] = $login;
				$customUIsByDiff[$diff][] = $customUI;
			}
		}
		// Third loop to add custom UIs
		foreach($loginsByDiff as $diff => $logins)
			$stackByPlayer[implode(',', $logins)][] = $customUIsByDiff[$diff];
		
		// Final loop to send manialinks
		$nextIsDialog = false;
		foreach($stackByPlayer as $login => $data)
		{
			Manialinks::load();
			foreach($data as $toDraw)
			{
				if($nextIsDialog) // this element can't be anything else than a window
				{
					$this->drawDialog($toDraw);
					$nextIsDialog = false;
				}
				else if($toDraw === self::NEXT_IS_DIALOG) // special delimiter for dialogs
					$nextIsDialog = true;
				else if(is_string($toDraw)) // a window's id alone means it has to be hidden
					$this->drawHidden($toDraw);
				else if(is_array($toDraw)) // custom ui's special case
				{
					array_shift($toDraw)->save();
					foreach($toDraw as $customUI)
						$customUI->hasBeenSaved();
				}
				else // else it can only be a window to show
					$this->drawWindow($toDraw);
			}
			$connection->sendDisplayManialinkPage($login, Manialinks::getXml(), 0, false, true);
		}
		$connection->executeMulticall();
		
		// Merging windows and deleting hidden ones to keep clean the current state
		foreach($this->nextWindows as $windowId => $visibilityByLogin)
		{
			if(isset($this->currentWindows[$windowId]))
				$newCurrent = array_filter(array_merge($this->currentWindows[$windowId], $visibilityByLogin));
			else
				$newCurrent = array_filter($visibilityByLogin);
			
			if($newCurrent)
				$this->currentWindows[$windowId] = $newCurrent;
			else
				unset($this->currentWindows[$windowId]);
		}
		$this->nextWindows = array();
		
		// After loops
		$endTime = microtime(true);
		$this->nextLoop += 0.2;
		// Profiling
		$this->sendingTimes[] = $endTime - $startTime;
		if (count($this->sendingTimes) >= 10)
		{
			$this->averageSendingTimes = array_sum($this->sendingTimes) / count($this->sendingTimes);
			$this->sendingTimes = array();
		}
	}
	
	final private function drawWindow(Window $window)
	{
		if($window instanceof ManagedWindow && $window->isMaximized())
			$window->setPosZ(Window::Z_MAXIMIZED);
		else
			$window->setPosZ($window->getMinZ());
		
		Manialinks::beginManialink($window->getId());
		$window->save();
		Manialinks::endManialink();
	}
	
	final private function drawDialog(Window $window)
	{
		$window->setPosZ(Window::Z_DIALOG + Window::Z_OFFSET);
		
		Manialinks::beginManialink($window->getId());
		$this->dialogBg->save();
		$window->save();
		Manialinks::endManialink();
	}
	
	final private function drawHidden($windowId)
	{
		Manialinks::beginManialink($windowId);
		Manialinks::endManialink();
	}
	
	function onPostLoop() {}
	function onTerminate() {}
	
	// Dedicated Listener
	
	function onPlayerConnect($login, $IsSpectator)
	{
		$this->hidingGui[$login] = false;
		$this->dialogs[$login] = array();
		$this->dialogShown[$login] = null;
		$this->managedWindow[$login] = null;
		$this->thumbnails[$login] = array();
		
		$sk = Shortkey::Create($login);
		$sk->addCallback(Shortkey::F8, array($this, 'toggleGui'));
		$sk->show();
	}
	
	function onPlayerDisconnect($login)
	{
		Window::Erase($login);
		CustomUI::Erase($login);
		
		unset($this->hidingGui[$login]);
		unset($this->dialogs[$login]);
		unset($this->dialogShown[$login]);
		unset($this->managedWindow[$login]);
		unset($this->thumbnails[$login]);
	}
	
	function onBeginMap($map, $warmUp, $matchContinuation) {}
	function onBeginMatch($map) {}
	function onBeginRound() {}
	function onBillUpdated($billId, $state, $stateName, $transactionId) {}
	function onMapListModified($curMapIndex, $nextMapIndex, $isListModified) {}
	function onEcho($internal, $public) {}
	function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap) {}
	function onEndMatch($rankings, $map) {}
	function onEndRound() {}
	function onManualFlowControlTransition($transition) {}
	function onPlayerChat($playerUid, $login, $text, $isRegistredCmd) {}
	function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex) {}
	function onPlayerFinish($playerUid, $login, $timeOrScore) {}
	function onPlayerIncoherence($playerUid, $login) {}
	function onPlayerInfoChanged($playerInfo) {}
	function onPlayerManialinkPageAnswer($playerUid, $login, $answer, array $entries) {}
	function onServerStart() {}
	function onServerStop() {}
	function onStatusChanged($statusCode, $statusName) {}
	function onTunnelDataReceived($playerUid, $login, $data) {}
	function onVoteUpdated($stateName, $login, $cmdName, $cmdParam) {}
	function onRulesScriptCallback($param1, $param2) {}
}

?>