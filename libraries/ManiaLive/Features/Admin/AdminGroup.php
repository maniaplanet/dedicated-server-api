<?php
/**
 * @copyright NADEO (c) 2010
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