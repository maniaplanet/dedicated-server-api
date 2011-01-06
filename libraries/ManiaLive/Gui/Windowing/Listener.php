<?php
/**
 * @copyright NADEO (c) 2010
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
}

?>