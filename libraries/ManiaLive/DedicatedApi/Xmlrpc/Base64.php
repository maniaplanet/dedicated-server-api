<?php

namespace ManiaLive\DedicatedApi\Xmlrpc;

class Base64 
{
	public $data;

	function __construct($data) {
		$this->data = $data;
	}

	function getXml() {
		return '<base64>'.base64_encode($this->data).'</base64>';
	}
}

?>