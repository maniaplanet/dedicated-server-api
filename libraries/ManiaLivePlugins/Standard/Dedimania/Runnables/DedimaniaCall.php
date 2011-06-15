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

use ManiaLive\Utilities\Logger;

if(!defined('LF'))
	define('LF', "\n");
if(!defined('CR'))
	define('CR', "\r");
if(!defined('CRLF'))
	define('CRLF', "\r\n");

use ManiaLivePlugins\Standard\Dedimania\Response;
use ManiaLivePlugins\Standard\Dedimania\Request;
use ManiaLivePlugins\Standard\Dedimania\Dedimania;
use ManiaLive\DedicatedApi\Xmlrpc\Request as XmlRpcRequest;
use ManiaLive\DedicatedApi\Xmlrpc\Message;

class DedimaniaCall extends \ManiaLive\Threading\Runnable
{
	public $requests;
	public $responses;

	static protected $connection = array();

	/**
	 * @var \ManiaLive\Utilities\Logger
	 */
	static protected $log = null;

	function addRequest($request)
	{
		$this->requests[] = $request;
	}

	function run()
	{
		$calls = array();

		// gets warnings and request time ...
		$this->requests[] = new Request('dedimania.WarningsAndTTR', array());

		// build multicall param ...
		foreach ($this->requests as $request)
		{
			$calls[] = array(
				'methodName' => $request->name,
				'params' => $request->params
			);
		}

		// build multiquery ...
		$multicall = new XmlRpcRequest('system.multicall', $calls);

		// return result ...
		$results = self::sendRequest($request->port, $request->file, $multicall);

		// reformat response because there can only be one param on first level ...
		// I think so at least :-)
		$return = array();
		foreach ($results as $result)
		{
			$response = null;
			if (isset($result['faultCode']) || isset($result['faultString']))
			{
				$response = array
				(
					'Error' => array
					(
						'Message' => $result['faultString'],
						'Code' => $result['faultCode']
					),
					'OK' => false
				);
			}
			else
			{
				if (is_array($result[0]))
				{
					$response = $result[0];
					$response['OK'] = ($result[0] != false && $result[0] != null);
				}
				else
				{
					$response = array();
					$response['OK'] = ($result[0] != false && $result[0] != null);
				}
			}
			$return[] = $response;
		}
		return $return;
	}

	static function connect($port)
	{
		if (isset(self::$connection[$port])) return self::$connection[$port];

		// dedimania.net
		// http://12.172.123.228:8080
		// open socket, use existing connection if there is one ...
		$errno = 0;
		$errstr = '';
		self::$connection[$port] = pfsockopen('dedimania.net', $port, $errno, $errstr, 30);

		// makes connection unblocking so we can read result ...
		stream_set_blocking(self::$connection[$port], false);

		// connection refused ...
		if (self::$connection[$port] == null) throw new \Exception('Connection failed!');

		// create logfile ...
		self::$log = Logger::getLog('XmlRpc', 'Dedimania');

		return self::$connection[$port];
	}

	/**
	 * Sends request to dedimania network ...
	 * @param \ManiaLive\DedicatedApi\XmlRpc\Request $request
	 */
	static function sendRequest($port, $file, $request)
	{
		$message = $request->getXml();
		$size = $request->getLength();
		$response = self::sendMessage($port, $file, $message);
		$msg = new Message($response);
		if ($msg === false)
		{
			return array();
		}
		$msg->parse();
		return $msg->params[0];
	}

	/**
	 * Sends Xml to the dedimania server using
	 * HTTP request.
	 * @param string $message
	 * @param integer $size
	 */
	static function sendMessage($port, $file, $message)
	{
		$connection = self::connect($port);

		if ($connection == null)
			throw new \Exception('Not Connected!');

		// prepare body ...
		$contents = gzdeflate($message, 9);

		// traffic log
		self::$log->write('REQUEST:' . APP_NL . $message . APP_NL . APP_NL);

		// send header ...
		if (fputs($connection, 'POST ' . $file . ' HTTP/1.1'.CRLF) === false)
		{
			return false;
		}

		fputs($connection, 'Host: dedimania.net'.CRLF);
		fputs($connection, 'User-Agent: XMLaccess'.CRLF);
		fputs($connection, 'Cache-Control: no-cache'.CRLF);
		fputs($connection, 'Content-Encoding: deflate'.CRLF);
		fputs($connection, 'Content-Type: text/xml; charset=UTF-8'.CRLF);
		fputs($connection, 'Content-Length: ' . strlen($contents) . CRLF);
		fputs($connection, 'Keep-Alive: timeout=600, max=2000'.CRLF);
		fputs($connection, 'Accept-Encoding: deflate'.CRLF);
		fputs($connection, 'Connection: Keep-Alive'.CRLF.CRLF);

		// send body ...
		fputs($connection, $contents);

		// receive response ...
		// start reading header information
		$size = 0;
		while (!feof($connection))
		{
			$line = fgets($connection, 128);

			// read content length ...
			if (($pos = strpos($line, 'Content-Length:')) !== false)
			{
				$size = intval(substr($line, $pos + 15));
			}

			if ($line == CRLF) break;
		}

		// read body ...
		$data = '';
		do
		{
			$line = fgets($connection);
			$data .= $line;
		}
		while (strlen($data) < $size);

		// decode body ...
		$body = gzinflate($data);

		// log xmlrpc traffic
		self::$log->write('RESPONSE:' . APP_NL . $body . APP_NL . APP_NL);

		// return body ...
		return $body;
	}
}
?>