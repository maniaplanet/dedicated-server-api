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

namespace ManiaLive\DedicatedApi;

class Config extends \ManiaLib\Utils\Singleton
{
	public $host = '127.0.0.1';
	public $port = 5000;
	public $user = 'SuperAdmin';
	public $password = 'SuperAdmin';
	public $timeout = 1;
}

?>