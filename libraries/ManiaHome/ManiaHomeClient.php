<?php
/**
 * @author Philippe Melot
 */

namespace ManiaHome;

use ManiaLive\Config\Loader;

define('APP_MANIAHOME_SERVICES_URL'	, 'http://maniahome.trackmania.com/services/?');

use ManiaHome\Rest\RestClient;

class ManiaHomeClient
{
	const NONE 		= 0;
	const TRACK 	= 1;
	const REPLAY 	= 2;
	
	protected $provider;
    protected $restClient;
	
    static function sendNotificationToPlayer($message, $login, $link, $type = self::NONE, $iconStyle = null, $iconSubStyle = null)
	{
		if (Loader::$config->maniahome->enabled)
		{
			$maniahome_client = new static();
		  $maniahome_client->send($message, $login, $link, $type, $iconStyle, $iconSubStyle);
		}
	}
	
	protected function __construct()
	{
	   $this->restClient = new \ManiaLib\Rest\Client(Loader::$config->maniahome->user, Loader::$config->maniahome->password);
		$this->provider = Loader::$config->maniahome->manialink;
	}
	
    protected function send($message, $login, $link, $type = self::NONE, $iconStyle = null, $iconSubStyle = null)
	{
		
		$body = array(
			'message' => $message,
		  'senderName' => $this->provider,
		  'receiverName' => $player,
            'link' => $link,
		  'type' => $type,
		  'iconStyle' => $iconStyle,
		  'iconStyle' => $iconSubStyle,
		);
	   try
		{
		  $this->restClient->execute('POST', '/maniahome/notification/', $body);
		}
	   catch(\Exception $e)
		{
		}
	}
}
?>