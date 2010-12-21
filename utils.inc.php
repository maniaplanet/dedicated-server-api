<?php

// TODO Faire un autoload propre et le mettre au bon endroit
function __autoload($classname)
{
	$items = explode('\\', $classname);
	if (!$items[0]) array_shift($items);
	$path = implode(DIRECTORY_SEPARATOR, $items);
	$path = __DIR__.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.$path.'.php';
	require_once $path;
}

spl_autoload_register('__autoload');

/**
 * Path to the application's root directory.
 * @var string
 */
define('APP_ROOT', __DIR__ . '/');

/**
 * Debugging mode switch.
 * @var bool
 */
define('APP_DEBUG', true);

// figure out the operating system
$os = strtoupper(substr(php_uname('s'), 0, 3));
if ($os == 'WIN')
{
	/**
	 * Operating System constant.
	 * Either WIN or UNIX.
	 * @var string
	 */
	define('APP_OS', 'WIN');
}
else
{
	define('APP_OS', 'UNIX');
}

// new line depending on system
if (APP_OS == 'WIN')
{
	/**
	 * New line constant.
	 * Depending on the host system.
	 * @var string
	 */
	define('APP_NL', "\r\n");
}
else
{
	define('APP_NL', "\n");
}
?>