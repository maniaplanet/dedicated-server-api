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

namespace ManiaHome;

class Client extends \ManiaLib\Utils\Singleton
{
	protected $provider;
	protected $restClient;

	static function sendNotificationToPlayer($message, $login, $link, $iconStyle = null, $iconSubStyle = null)
	{
		if(Config::getInstance()->enabled)
		{
			$maniahome_client = self::getInstance();
			$maniahome_client->send($message, $login, $link, $iconStyle, $iconSubStyle);
		}
	}

	protected function __construct()
	{
		$config = Config::getInstance();
		$this->restClient = new \ManiaLib\Rest\Client($config->user, $config->password);
		$this->provider = $config->manialink;
	}

	protected function send($message, $login, $link, $iconStyle = null, $iconSubStyle = null)
	{
		$body = array(
			'message' => $message,
			'senderName' => $this->provider,
			'receiverName' => $login,
			'link' => $link,
			'iconStyle' => $iconStyle,
			'iconSubStyle' => $iconSubStyle
		);
		try
		{
			$this->restClient->execute('POST', '/maniahome/notification/%s/', array($login, $body));
		}
		catch(\Exception $e)
		{

		}
	}
}

?>