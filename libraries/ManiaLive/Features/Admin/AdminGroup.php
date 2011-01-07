<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 1709 $:
 * @author      $Author: svn $:
 * @date        $Date: 2011-01-07 14:06:13 +0100 (ven., 07 janv. 2011) $:
 */

namespace ManiaLive\Features\Admin;

use ManiaLive\Config\Loader;

abstract class AdminGroup
{
	public static function contains($login)
	{
		$login = explode('/', $login, 1);
		return (array_search($login[0], Loader::$config->admins->logins) !== false);
	}

	public static function get()
	{
		return  Loader::$config->admins->logins;
	}
}

?>