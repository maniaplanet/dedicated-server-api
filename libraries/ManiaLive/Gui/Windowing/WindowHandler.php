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
	protected $drawstack_count;
	protected $drawstack;
	protected $uptodate;
	protected $storage;
	
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
	 * Draws the Windows, that have been modified this loop, in correct order.
	 * @see libraries/ManiaLive/Application/ManiaLive\Application.Listener::onPostLoop()
	 */
	function onPreLoop()
	{
		if ($this->drawstack_count == 0) 
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
			$window_prev = null;
			foreach ($finalstack as $window)
			{	
				$window->prev = $window_prev;
				$window->render($login);
				$window_prev = $window;
			}
		}
		
		$this->drawstack = array();
		$this->drawstack_count = 0;
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
		$this->drawstack_count++;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/DedicatedApi/Callback/ManiaLive\DedicatedApi\Callback.Listener::onPlayerDisconnect()
	 */
	function onPlayerDisconnect($login)
	{
		Window::destroyPlayerWindows($login);
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