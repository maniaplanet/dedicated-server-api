<?php

namespace ManiaLive\Gui\Windowing;

interface Listener extends \ManiaLive\Event\Listener
{
	/**
	 * A window is being closed.
	 * @param unknown_type $login Login of the player who's closed the window.
	 * @param unknown_type $window Reference to the specific window.
	 */
	public function onWindowClose($login, $window);
}

?>