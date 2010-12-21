<?php
/**
 * @author Philippe Melot
 */

namespace ManiaHome;

use ManiaLive\Config\Loader;

define('APP_MANIAHOME_SERVICES_URL'	, 'http://maniahome.trackmania.com/services/?');

use ManiaHome\Rest\RestClient;

class ManiaHomeClient extends RestClient
{
	const NONE 		= 0;
	const TRACK 	= 1;
	const REPLAY 	= 2;
	
	protected $provider;
	
	static function sendNotificationToPlayer($message, $login, $link, $type = 0)
	{
		if (Loader::$config->maniahome->enabled)
		{
			$maniahome_client = new static();
			$maniahome_client->send($message, $login, $link, $type);
		}
	}
	
	protected function __construct()
	{
		parent::__construct(Loader::$config->maniahome->user, Loader::$config->maniahome->password);
		$this->provider = Loader::$config->maniahome->manialink;
	}
	
	protected function send($message, $login, $link, $type = 0)
	{
		
		$this->url = APP_MANIAHOME_SERVICES_URL.'c=Notifications&m=setNotification';
		$this->request->setUrl($this->url);
		
		$this->request->setVerb('POST');
		//Préparation du corps de la requête
		$body = array(
			'message' => $message,
            'emetteur' => $this->provider,
            'login' => $login,
            'link' => $link,
            'type' => $type
		);
		$this->request->buildPostBody($body);
		//Exéctuin
		$this->request->execute();
		//Récupération du header et du corps de la réponse
		$infos = $this->request->getResponseInfo();
		$this->responseBody = $this->request->getResponseBody();
		$this->flush();
		if($infos['http_code'] == 200)
		{
			return true;
		}
		else
		{
			$this->lastError = $infos['http_code'];
			return false;
		}
	}
}
?>