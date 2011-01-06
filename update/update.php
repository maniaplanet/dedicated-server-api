<?php

const NL = "\n";

echo '###############################' . NL;
echo '# ManiaLive Updater' . NL;
echo '###############################' . NL;
echo NL;

echo '> Checking local ManiaLive version ...' . NL;

if (file_exists('../noupdate')) die('ERROR: This version is locked for updates!' . NL);

$includes = array(
	'../libraries/ManiaLive/Utilities/Singleton.php',
	'../libraries/ManiaLive/Application/AbstractApplication.php',
	'../libraries/ManiaLiveApplication/Application.php'
);

$success = true;
foreach ($includes as $inc)
	if (file_exists($inc)) include_once($inc); else $success = false;

if ($success)
	$version_local = \ManiaLiveApplication\Version;
else
	$version_local = 0;

echo '> ManiaLive is at version ' . $version_local . NL;

echo '> Checking remote ManiaLive version ...' . NL;

try
{
	$version_remote = file_get_contents('http://manialink.manialive.com/public/version');
	$version_remote = intval($version_remote);
}
catch (\Exception $ex)
{
	die('ERROR: Could not get the remote version!'. NL);
}

echo '> Remote ManiaLive is at version ' . $version_remote . NL;

if ($version_local >= $version_remote)
	die ('> Local version is already uptodate, taking no action!' . NL);

echo '> Searching ManiaLive release package ...' . NL;

$package = 'ManiaLive_1.0_r' . $version_remote . '.zip';
$url = 'http://manialive.googlecode.com/files/' . $package . '';

if (file_exists($url)) die('ERROR: File could not be found!');

echo "> downloading '" . $package . "' ..." .NL;

// download and save the package 
$data = file_get_contents($url);
file_put_contents('./temp/' . $package, $data);

echo '> extract files ...' . NL;

if (!class_exists('ZipArchive'))
{
	die('class ZipArchive does not exist, you need to'.
	'enable the zip extension for your php version!');
}

$zip = new ZipArchive;
$res = $zip->open('./temp/' . $package);

if ($res !== true)
	die('ERROR: Could not extract zip archive!');

$zip->extractTo('./temp/');
$zip->close();

echo 'Removing old directories ...' . NL;
deleteDir('../libraries/ManiaLive');
deleteDir('../libraries/ManiaLiveApplication');
deleteDir('../libraries/ManiaHome');
unlink('../bootstrapper.php');
unlink('../utils.inc.php');
unlink('../LICENSE');
unlink('../README');
unlink('../CONVENTIONS');

echo 'Copying new files ...' . NL;
rcopy('./temp/ManiaLive/libraries/ManiaLive', '../libraries/ManiaLive');
rcopy('./temp/ManiaLive/libraries/ManiaLiveApplication', '../libraries/ManiaLiveApplication');
rcopy('./temp/ManiaLive/libraries/ManiaHome', '../libraries/ManiaHome');
copy('./temp/ManiaLive/bootstrapper.php', '../bootstrapper.php');
copy('./temp/ManiaLive/utils.inc.php', '../utils.inc.php');
copy('./temp/ManiaLive/LICENSE', '../LICENSE');
copy('./temp/ManiaLive/README', '../README');
copy('./temp/ManiaLive/CONVENTIONS', '../CONVENTIONS');

echo '> Cleaning up ...' . NL;

deleteDir('./temp/ManiaLive');

echo '>> Done!' . NL;

function deleteDir($dir) 
{ 
	if (!is_dir($dir)) return;

   if (substr($dir, strlen($dir)-1, 1) != '/') 
       $dir .= '/'; 

   echo 'deleting: ' . $dir . NL; 

   if ($handle = opendir($dir)) 
   { 
       while ($obj = readdir($handle)) 
       { 
           if ($obj != '.' && $obj != '..') 
           { 
               if (is_dir($dir.$obj)) 
               { 
                   deleteDir($dir.$obj);
               } 
               elseif (is_file($dir.$obj)) 
               { 
                   unlink($dir.$obj);
               } 
           } 
       } 
       closedir($handle); 
       return rmdir($dir);
   } 
   return false; 
}  

function rcopy($src, $dst) { 
	$dir = opendir($src); 
	@mkdir($dst);
	while(false !== ( $file = readdir($dir)) ) { 
		if (( $file != '.' ) && ( $file != '..' )) { 
			if ( is_dir($src . '/' . $file) ) { 
				rcopy($src . '/' . $file, $dst . '/' . $file); 
			} 
			else { 
				echo 'copying: ' . $src . NL;
				copy($src . '/' . $file, $dst . '/' . $file); 
			} 
		} 
	} 
	closedir($dir); 
}
?>