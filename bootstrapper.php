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

$php_ok = (function_exists('version_compare') && version_compare(phpversion(), '5.3.1', '>='));
$json_ok = (extension_loaded('json') && function_exists('json_encode') && function_exists('json_decode'));
$spl_ok = extension_loaded('spl');
if (function_exists('curl_version'))
{
	$curl_version = curl_version();
	$curl_ok = (function_exists('curl_exec') && in_array('https', $curl_version['protocols'], true));
}
else
{
    $curl_ok = false;
}
$sqlite2_ok = extension_loaded('sqlite');
$sqlite3_ok = extension_loaded('sqlite3');
$sqlite_ok = ($sqlite2_ok || $sqlite3_ok);


function success($s = 'Yes')
{
	return "[ " . $s . " ]";
}

function failure($s = 'No ')
{
	return "[ " . $s . " ]";
}
echo
'
  ##################################     ###################################
  ##################################     ###################################
                                                                         ###
  ################  ################     ###  ############  ###########  ###
  ################  ################     ###  ############  ###########  ###
               ###  ###                  ###  ###      ###  ###     ###  ###
               ###  ###                  ###  ###      ###  ###     ###  ###
               ###  ###                  ###  ###      ###  ###     ###  ###
               ###  ###                  ###  ###      ###  ###     ###  ###
               ###  ###                  ###  ###      ###  ###     ###  ###
               ###  ###                  ###  ###      ###  ###     ###  ###
';
echo 'ManiaLive' . PHP_EOL;
echo 'PHP Environment Compatibility Test' . PHP_EOL;
echo '-----------------------------------------------------' . PHP_EOL;
echo 'PHP 5.3.1 or newer    -> required  -> ' . ($php_ok ? (success() . ' ' . phpversion()) : failure()) . PHP_EOL;
echo 'Standard PHP Library  -> required  -> ' . ($spl_ok ? success() : failure()) . PHP_EOL;
echo 'JSON                  -> required  -> ' . ($json_ok ? success() : failure()) . PHP_EOL;
echo 'cURL with SSL         -> required  -> ' . ($curl_ok ? (success() . ' ' . $curl_version['version'] . ' (' . $curl_version['ssl_version'] . ')' . (is_array($curl_version['protocols']) && in_array('https', $curl_version['protocols'], true) ? ' (with ' . $curl_version['ssl_version'] . ')' : ' (without SSL)')) : failure()) . PHP_EOL;
echo 'SQLite                -> optional  -> ' . ($sqlite_ok ? success() : failure()) . PHP_EOL;
echo '-----------------------------------------------------' . PHP_EOL;


if(!$php_ok || !$curl_ok || !$spl_ok || !$json_ok)
{
    echo 'Your system is not compatible, check your php configuration.';
    exit;
}

if(!$sqlite_ok)
{
    echo 'SQLite is disabled, threading will not work. ManiaLive may encounter some perfomance trouble.'.PHP_EOL;
}
// enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// include the __autoload function ...
require_once __DIR__ . '/utils.inc.php';

ManiaLiveApplication\Application::getInstance()->run();
?>