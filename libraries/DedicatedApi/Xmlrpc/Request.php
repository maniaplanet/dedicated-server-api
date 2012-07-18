<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 * Based on
 * GbxRemote by Nadeo and
 * IXR - The Incutio XML-RPC Library - (c) Incutio Ltd 2002
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace DedicatedApi\Xmlrpc;

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