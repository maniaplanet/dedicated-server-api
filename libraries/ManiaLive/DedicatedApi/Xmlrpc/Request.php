<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\DedicatedApi\Xmlrpc;

class Request 
{
	public $method;
	public $args;
	public $xml;

	function __construct($method, $args) 
	{
		$this->method = $method;
		$this->args = $args;
		$this->xml = '<?xml version="1.0" encoding="utf-8" ?><methodCall><methodName>' . $this->method . '</methodName><params>';
		foreach ($this->args as $arg) 
		{
			$this->xml .= '<param><value>';
			$v = new Value($arg);
			$this->xml .= $v->getXml();
			$this->xml .= '</value></param>' . LF;
		}
		$this->xml .= '</params></methodCall>';
	}

	function getLength() 
	{
		return strlen($this->xml);
	}

	function getXml() 
	{
		return $this->xml;
	}
}

?>