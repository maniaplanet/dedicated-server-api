<?php

namespace ManiaHome\Rest;

use ManiaHome\Rest\RestRequest;
use ManiaHome\Rest\RestRequestCurl;

abstract class RestClient
{
	protected $request;
	protected $url = APP_MANIAHOME_SERVICES_URL;
	protected $lastInsert;
	protected $lastError;
	protected $responseBody;
	
	protected function __construct($username, $password)
	{
		if(extension_loaded('curl'))
			$this->request = new RestRequestCurl();
		else
			$this->request = new RestRequest();
		
		$this->url .= 'c='.get_class($this);
		$this->request->setUsername($username);
		$this->request->setPassword($password);
	}
	
	function flush()
	{
		$this->request->flush();
		$this->url = APP_MANIAHOME_SERVICES_URL.'c='.get_class($this);
	}
	
	function getLastInsert()
	{
		return $this->lastInsert;
	}
	
	function getLastError()
	{
		return $this->lastError;
	}
	
	function getResponseBody()
	{
		return $this->responseBody;
	}
	
	function getUrl()
	{
		return $this->url;
	}
}