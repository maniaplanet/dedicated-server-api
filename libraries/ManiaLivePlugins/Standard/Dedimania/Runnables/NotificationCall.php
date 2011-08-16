<?php

namespace ManiaLivePlugins\Standard\Dedimania\Runnables;

class NotificationCall extends \ManiaLive\Threading\Runnable
{
	public $message;
	public $login;
	public $link;
	public $type;
	
	function __construct($message, $login, $type, $link = '')
	{
		$this->message = $message;
		$this->login = $login;
		$this->type = $type;
		$this->link = $link;
	}
	
	function run()
	{
		\ManiaHome\Client::sendNotificationToPlayer($this->message, $this->login, $this->link, $this->type);
	}
}

?>