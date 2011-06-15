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

use ManiaLive\Config\Loader;

class ManiaHomeClient
{
    protected $provider;
    protected $restClient;

    static function sendNotificationToPlayer($message, $login, $link, $iconStyle = null, $iconSubStyle = null)
    {
	   if(Loader::$config->maniahome->enabled)
	   {
		  $maniahome_client = new static();
		  $maniahome_client->send($message, $login, $link, $iconStyle, $iconSubStyle);
	   }
    }

    protected function __construct()
    {
	   $this->restClient = new \ManiaLib\Rest\Client(Loader::$config->maniahome->user, Loader::$config->maniahome->password);
	   $this->provider = Loader::$config->maniahome->manialink;
    }

    protected function send($message, $login, $link, $iconStyle = null, $iconSubStyle = null)
    {
	   $body = array(
		  'message' => $message,
		  'senderName' => $this->provider,
		  'receiverName' => $login,
		  'link' => $link,
		  'iconStyle' => $iconStyle,
		  'iconStyle' => $iconSubStyle,
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