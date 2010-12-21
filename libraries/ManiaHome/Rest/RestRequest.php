<?php

namespace ManiaHome\Rest;

class RestRequest
{
	protected $params = array();
	protected $url;
	protected $verb;
	protected $requestBody;
	protected $requestLength;
	protected $username;
	protected $password;
	protected $acceptType;
	protected $responseBody;
	protected $responseInfo;
	
	public function __construct ($url = null, $verb = 'GET', $requestBody = null)
	{
		$this->url				= $url;
		$this->verb				= $verb;
		$this->requestBody		= $requestBody;
		$this->requestLength	= 0;
		$this->username			= null;
		$this->password			= null;
		$this->acceptType		= 'text/xml';
		$this->responseBody		= null;
		$this->responseInfo		= null;
		
		if ($this->requestBody !== null && $verb == 'POST')
		{
			$this->buildPostBody();
		}
	}
	
	public function flush ()
	{
		$this->requestBody		= null;
		$this->requestLength	= 0;
		$this->verb				= 'GET';
		$this->responseBody		= null;
		$this->responseInfo		= null;
	}
	
	public function execute ()
	{
		try
		{
			$options = array();
			$options['http'] = array();
			$options['http']['timeout'] = 10;
			$httpResource = stream_context_create();
			switch (strtoupper($this->verb))
			{
				case 'GET':
					$this->executeGet($httpResource);
					break;
				case 'POST':
					$this->executePost($httpResource);
					break;
				case 'PUT':
					$this->executePut($httpResource);
					break;
				case 'DELETE':
					$this->executeDelete($httpResource);
					break;
				default:
					throw new InvalidArgumentException('Current verb (' . $this->verb . ') is an invalid REST verb.');
			}
		}
		catch (InvalidArgumentException $e)
		{
			throw $e;
		}
		catch (Exception $e)
		{
			throw $e;
		}
		
	}
	
	public function buildPostBody ($data = null)
	{
		$data = ($data !== null) ? $data : $this->requestBody;
		
		if (!is_array($data))
		{
			throw new InvalidArgumentException('Invalid data input for postBody.  Array expected');
		}
		
		$data = http_build_query($data, '', '&');
		$this->requestBody = $data;
	}
	
	protected function executeGet ($httpResource)
	{		
		$this->doExecute($httpResource);	
	}
	
	protected function executePost ($httpResource)
	{
		if (!is_string($this->requestBody))
		{
			$this->buildPostBody();
		}
		$header = 'Content-length: '.strlen($this->requestBody)."\n";
		$header .= 'Content-type: application/x-www-form-urlencoded; charset=UTF-8'; 
		
		if(!stream_context_set_option($httpResource, 'http', 'method', 'POST'))
			throw new Exception("fail to set POST method");
		if(!stream_context_set_option($httpResource, 'http', 'content', $this->requestBody))
			throw new Exception("fail to set content");
		
		if(!stream_context_set_option($httpResource, 'http', 'header', $header))
			throw new Exception("fail to set header");
		
		$this->doExecute($httpResource);	
	}
	
	protected function executePut ($httpResource)
	{
		if (!is_string($this->requestBody))
		{
			$this->buildPostBody();
		}
		
		$this->requestLength = strlen($this->requestBody);
		$options = array();
			
		$fh = fopen('php://temp', 'rw');
		fwrite($fh, $this->requestBody);
		rewind($fh);
		
		$header = 'Content-length: '.strlen($this->requestBody)."\n";
		$header .= 'Content-type: application/x-www-form-urlencoded'; 
		
		if(!stream_context_set_option($httpResource, 'http', 'method', 'PUT'))
			throw new Exception("fail to set PUT method");
		if(!stream_context_set_option($httpResource, 'http', 'content', $this->requestBody))
			throw new Exception("fail to set content");
		
		if(!stream_context_set_option($httpResource, 'http', 'header', $header))
			throw new Exception("fail to set header");
		
		$this->doExecute($httpResource);	
		
		fclose($fh);
	}
	
	protected function executeDelete ($httpResource)
	{
		if(!stream_context_set_option($httpResource, 'http', 'method', 'DELETE'))
			throw new Exception("fail to set DELETE method");
				
		$this->doExecute($httpResource);
	}
	
	protected function doExecute (&$httpResource)
	{
		$this->setOpts($httpResource);
		$this->setAuth();
		$errorLevel = error_reporting(0);
		$this->responseBody = file_get_contents($this->url, 'FILE_BINARY', $httpResource);
		$tmp = $http_response_header;
		$this->responseInfo = array();
		$this->responseInfo['http_code'] = (int) preg_replace('/(?:.*)([1-5]\\d{2}){1}(?:.*)/uix', '$1', $tmp[0]);
		$this->responseInfo['content_type'] = str_ireplace('Content-Type: ', '', $tmp[6]);
		error_reporting($errorLevel);
		//curl_close($curlHandle);
	}
	
	protected function setOpts (&$httpResource)
	{
		$options = array();
		$options['timeout'] = 10;
		stream_context_set_params($httpResource, $options);
		//curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array ('Accept: ' . $this->acceptType));
	}
	
	protected function setAuth ()
	{
		if ($this->username !== null && $this->password !== null)
		{
			$this->url = str_ireplace('http://','http://'.$this->username.':'.$this->password.'@', $this->url);
		}
	}
	
	public function getAcceptType ()
	{
		return $this->acceptType;
	} 
	
	public function setAcceptType ($acceptType)
	{
		$this->acceptType = $acceptType;
	} 
	
	public function getPassword ()
	{
		return $this->password;
	} 
	
	public function setPassword ($password)
	{
		$this->password = $password;
	} 
	
	public function getResponseBody ()
	{
		return $this->responseBody;
	} 
	
	public function getResponseInfo ()
	{
		return $this->responseInfo;
	} 
	
	public function getUrl ()
	{
		return $this->url;
	} 
	
	public function setUrl ($url)
	{
		$this->url = $url;
	} 
	
	public function getUsername ()
	{
		return $this->username;
	} 
	
	public function setUsername ($username)
	{
		$this->username = $username;
	} 
	
	public function getVerb ()
	{
		return $this->verb;
	} 
	
	public function setVerb ($verb)
	{
		$this->verb = $verb;
	} 
}
