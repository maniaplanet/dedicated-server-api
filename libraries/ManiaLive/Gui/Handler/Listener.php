<?php

namespace ManiaLive\Gui\Handler;

interface Listener extends \ManiaLive\Event\Listener
{
	public function onActionClick($login, $action);
}

?>