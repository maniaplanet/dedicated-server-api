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

/**
 * @author Florian Schnell
 */
interface Listener extends \ManiaLive\Event\Listener
{
	/**
	 * A window is being closed.
	 * @param string $login Login of the player who's closed the window.
	 * @param \ManiaLive\Gui\Windowing\Window $window Reference to the specific window.
	 */
	public function onWindowClose($login, $window);
	
	/**
	 * Window was hidden and is now displayed again.
	 * @param string $login
	 * @param \ManiaLive\Gui\Windowing\Window $window
	 */
	public function onWindowRecover($login, $window);
}

?>