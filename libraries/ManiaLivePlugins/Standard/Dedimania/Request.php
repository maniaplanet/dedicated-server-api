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
namespace ManiaLivePlugins\Standard\Dedimania;

class Request extends \ManiaLive\DedicatedApi\Xmlrpc\Request
{
	public $server;
	public $name;
	public $params;
	public $port;
	public $file;

	const SERVER_AUTH = 1;
	const SERVER_TEST = 2;
	const SERVER_WORK = 3;

	/**
	 * Creates a new Dedimania Request.
	 * @param $method_name
	 * @param $params
	 * @param $server
	 */
	function __construct($method_name, $params = array(), $server = self::SERVER_WORK)
	{
		$this->name = $method_name;
		$this->params = $params;
		$this->setServer($server);
	}

	/**
	 * Set destination server for Request.
	 * @param $server
	 */
	function setServer($server)
	{
		$this->server = $server;
		switch ($this->server)
		{
			case Request::SERVER_AUTH:
				$this->port = 80;
				$this->file = '/RPC4/server.php';
				break;

			case Request::SERVER_TEST:
				$this->port = 8001;
				$this->file = '/Dedimania';
				break;

			case Request::SERVER_WORK:
				$this->port = 8002;
				$this->file = '/Dedimania';

			default:
				$this->port = 8002;
				$this->file = '/Dedimania';
				break;
		}
	}
}

?>