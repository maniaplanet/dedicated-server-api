<?php
/**
 * @author NewboO
 */

namespace Maniaplanet\DedicatedServer\Transport;

class Base64
{
	private $data;

	function __construct($data)
	{
		$this->data = base64_encode($data);
	}

	function __toString()
	{
		return $this->data;
	}
}

?>