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

class Base64 
{
	public $data;

	function __construct($data)
	{
		$this->data = $data;
	}

	function getXml() 
	{
		return '<base64>'.base64_encode($this->data).'</base64>';
	}
}

?>