<?php
/**
 * ManiaLib - Lightweight PHP framework for Manialinks
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLib\Rest;

/**
 * Lightweight REST client for Web Services.
 * 
 * Requires CURL and JSON extensions
 * 
 * Example:
 * <code>
 * try 
 * {
 *     $client = new \ManiaLib\Rest\Client('user', 'pa55w0rd');
 *     var_dump($client->execute('GET', '/foobar/'));
 *     var_dump($client->execute('POST', '/foobar/', array(
 *         array(
 *             'anInt' => 1,
 *             'aString' => 1,
 *             'anObject' => 1,
 *             'anArray' => 1,
 *         )
 *     )));
 * }
 * catch(\Exception $e)
 * {
 *     var_dump($e);
 * }
 * </code>
 */
class Client
{
	protected $APIURL = 'https://api.maniastudio.com';
	protected $username;
	protected $password;
	protected $contentType;
	protected $acceptType;
	protected $serializeCallback;
	protected $unserializeCallback;
	protected $timeout;
	
	function __construct($username = null, $password = null) 
	{
		if (!function_exists('curl_init')) 
		{
			throw new \Exception(sprintf('%s needs the CURL PHP extension.', get_called_class()));
		}
		$this->username = $username?:Config::getInstance()->username;
		$this->password = $password?:Config::getInstance()->password;
		$this->contentType = 'application/json';
		$this->acceptType = 'application/json';
		$this->serializeCallback = 'json_encode';
		$this->unserializeCallback = 'json_decode';
		$this->timeout = 3;
	}

	function setAuth($username, $password)
	{
		$this->username = $username;
		$this->password = $password;
	}
	
	function setAPIURL($URL)
	{
		$this->APIURL = $URL;
	}
	
	function setContentType($contentType)
	{
		$this->contentType = $contentType;
	}
	
	function setAcceptType($acceptType)
	{
		$this->acceptType = $acceptType;
	}
	
	function setSerializeCallback($callback)
	{
		$this->serializeCallback = $callback;
	}
	
	function setUnserializeCallback($callback)
	{
		$this->unserializeCallback = $callback;
	}
	
	function setTimeout($timeout)
	{
		$this->timeout = $timeout;
	}
	
	function execute($verb, $ressource, array $params = array())
	{
		$url = $this->APIURL.$ressource;
		if($verb == 'POST' || $verb == 'PUT')
		{
			 $data = array_pop($params);
			 $data = call_user_func($this->serializeCallback, $data);
		}
		else
		{
			$data = null;
		}
		if($params)
		{
			$params = array_map('urlencode', $params);
			array_unshift($params, $url);
			$url = call_user_func_array('sprintf', $params);
		}
		
		$header[] = 'Accept: '.$this->acceptType;
		$header[] = 'Content-type: '.$this->contentType;
		
		$options = array();
		
		switch($verb)
		{
			case 'GET':
				// Nothing to do
				break;
				
			case 'POST':
				$options[CURLOPT_POST] = true;
				$options[CURLOPT_POSTFIELDS] = $data;
				break;
			
			case 'PUT':
				$fh = fopen('php://temp', 'rw');
				fwrite($fh, $data);
				rewind($fh);
				
				$options[CURLOPT_PUT] = true;
				$options[CURLOPT_INFILE] = $fh;
				$options[CURLOPT_INFILESIZE] = strlen($data);
				break;
				
			case 'DELETE':
				$options[CURLOPT_POST] = true;
				$options[CURLOPT_POSTFIELDS] = '';
				$header[] = 'Method: DELETE';
				break;
				
			default:
				throw new \InvalidArgumentException('Unsupported HTTP method: '.$verb);
		}
		
		$options[CURLOPT_URL] = $url;
		$options[CURLOPT_HTTPHEADER] = $header;
		$options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
		$options[CURLOPT_USERPWD] = $this->username.':'.$this->password;
		$options[CURLOPT_TIMEOUT] = $this->timeout;
		$options[CURLOPT_RETURNTRANSFER] = true;
		$options[CURLOPT_USERAGENT] = 'ManiaLib Rest Client (2.0 preview)'; 
		
		// This normally should not be done
		// But the certificates of our api are self-signed for now
		$options[CURLOPT_SSL_VERIFYHOST] = 0;
		$options[CURLOPT_SSL_VERIFYPEER] = 0;
		
		try 
		{
			$ch = curl_init();
			curl_setopt_array($ch, $options);
			$response = curl_exec($ch);
			$info = curl_getinfo($ch);
			curl_close($ch);
		}
		catch(\Exception $e)
		{
			if($ch)
			{
				curl_close($ch);
			}
			throw $e;
		}
		
		if($response && $this->unserializeCallback)
		{
			$response = call_user_func($this->unserializeCallback, $response);
		}
		
		if($info['http_code'] == 200)
		{
			return $response;
		}
		else
		{
			if(is_object($response) && property_exists($response, 'message'))
			{
				$message = $response->message;
			}
			else
			{
				$message = 'API error. Check the HTTP error code.';
			}
			throw new \Exception($message, $info['http_code']);
		}
	}
}

?>