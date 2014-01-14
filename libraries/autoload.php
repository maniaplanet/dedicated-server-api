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

define('APP_ROOT', __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR);

if(!defined('MANIALIB_APP_PATH'))
{
	define('MANIALIB_APP_PATH', __DIR__.'/../');
}

spl_autoload_register(
		function ($className)
		{
			$className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
			$path = APP_ROOT.'libraries'.DIRECTORY_SEPARATOR.$className.'.php';
			if(file_exists($path))
				require_once $path;
		}
);

$composerAutoloaderFile = __DIR__.'/../vendor/autoload.php';

if (file_exists($composerAutoloaderFile))
	require $composerAutoloaderFile;


?>