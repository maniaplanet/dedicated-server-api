<?php
/**
 * @author NewboO
 */

namespace Maniaplanet\DedicatedServer\Transport;

class Base64
{
	public $scalar;
	public $xmlrpc_type = 'base64';

	function __construct($data)
	{
		$this->scalar = $data;
	}
}

?>