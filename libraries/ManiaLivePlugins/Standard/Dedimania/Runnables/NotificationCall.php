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
namespace ManiaLivePlugins\Standard\Dedimania\Runnables;

use ManiaHome\ManiaHomeClient;

class NotificationCall extends \ManiaLive\Threading\Runnable
{
	public $message;
	public $login;
	public $link;
	public $iconStyle;
	public $iconSubstyle;

	function __construct($message, $login, $link = '', $iconStyle = null, $iconSubstyle = null)
	{
		$this->message = $message;
		$this->login = $login;
		$this->link = $link;
		$this->iconStyle = $iconStyle;
		$this->iconSubstyle = $iconSubstyle;
	}

	function run()
	{
		ManiaHomeClient::sendNotificationToPlayer($this->message, $this->login, $this->link, $this->iconStyle, $this->iconSubstyle);
	}
}

?>