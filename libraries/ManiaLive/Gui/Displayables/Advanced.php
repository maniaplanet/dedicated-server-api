<?php
namespace ManiaLive\Gui\Displayables;

abstract class Advanced extends \ManiaLive\Gui\Handler\Displayable
{
	abstract function onClicked($playerUid, $login, $action, $param);
}
?>