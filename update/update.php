<?php

const NL = "\n";

echo '###############################' . NL;
echo '# ManiaLive Updater' . NL;
echo '###############################' . NL;
echo NL;

echo '> Checking local ManiaLive version ...' . NL;

// the noupdate file will prevent the updater from working its magic.
if (file_exists('../noupdate')) 
{
	die('ERROR: This version is locked for updates!' . NL);
}

$includes = array(
	'../libraries/ManiaLib/Rest/Client.php',
	'../libraries/ManiaLib/Utils/Singleton.php',
	'../libraries/ManiaLive/Application/AbstractApplication.php',
	'../libraries/ManiaLiveApplication/Application.php'
);

// start loading dependencies
$success = true;
foreach ($includes as $inc)
{
	if (file_exists($inc))
	{
		include_once($inc);
	}
	else
	{
		$success = false;
	}
}

// if everything's there, then get the version number
if ($success)
{
	$versionLocal = \ManiaLiveApplication\Version;
}
else // otherwise something broke and we need to do an update!
{
	$versionLocal = 0;
}

echo '> ManiaLive is at version ' . $versionLocal . NL;

echo '> Checking remote ManiaLive version ...' . NL;

// check for manialive update
$versionRemote = 0;
$versionDownloadUrl = '';
$versionUpdate = false;
try
{
	$client = new \ManiaLib\Rest\Client();
	$client->setAPIURL('http://api.maniastudio.com');
	$response = $client->execute('GET', '/manialive/version/check/' . \ManiaLiveApplication\Version . '/index.json');
	$versionUpdate = $response->update;
	$versionRemote = $response->version->revision;
	$versionDownloadUrl = $response->version->downloadUrl;
}
catch (\Exception $ex)
{
	die('ERROR: It is currently not possible to access manialive webservice!' . NL);
}

echo '> Remote ManiaLive is at version ' . $versionRemote . NL;

// no need to update when there's already the latest version installed
if (!$versionUpdate)
{
	echo 'Local version is the same or even newer than the remote one.' . NL;
	echo 'Do you want to proceed? (y/n):';
	
	$in = strtolower(trim(fgets(STDIN)));
	if ($in != 'y')
	{
		die ('> Local version is already uptodate, taking no action!' . NL);
	}
	
	echo NL;
}

// create temporary folder
if (!is_dir('./temp'))
{
	echo "> Creating temporary directory ..." .NL;
	mkdir('./temp');
}

// get file name
$info = pathinfo($versionDownloadUrl);
$package = $info['basename'];

echo "> Downloading '" . $package . "' ..." .NL;

// download and save the package
$data = @file_get_contents($versionDownloadUrl);

// check for errors
if ($data === false)
{
	die('ERROR: The file could not be retrieved from the server!' . NL);
}

echo "> OK." . NL;

file_put_contents('./temp/' . $package, $data);

echo NL;

echo 'Everything is in place.' . NL;
echo '[local: ' . $versionLocal . '] ---> [remote: ' . $versionRemote . ']' . NL;
echo 'Do you want to update ManiaLive now? (y/n):';

// parsing user input
$in = strtolower(trim(fgets(STDIN)));
if ($in != 'y')
{
	echo NL;
	
	echo '> Cleaning up!' . NL;
	
	rrmdir('./temp');
	
	die('Aborted by user!' . NL);
}

echo '> Extract files ...' . NL;

// check if zip library is loaded ...
if (!class_exists('ZipArchive'))
{
	die('class ZipArchive does not exist, you need to'.
		'enable the zip extension for your php version!' . NL);
}

// try to extract the archive
$zip = new ZipArchive;
$res = $zip->open('./temp/' . $package);

if ($res !== true)
{
	die('ERROR: Could not extract zip archive!' . NL);
}

$zip->extractTo('./temp/');
$zip->close();

echo NL;

echo '> Removing old directories ...' . NL;
rrmdir('../libraries/ManiaLive');
rrmdir('../libraries/ManiaLiveApplication');
rrmdir('../libraries/ManiaHome');
rrmdir('../libraries/ManiaLib');
@unlink('../config/config-example.ini');
@unlink('../bootstrapper.php');
@unlink('../utils.inc.php');
@unlink('../LICENSE');
@unlink('../README');
@unlink('../CONVENTIONS');
@unlink('../update/update.php');
@unlink('../run');
@unlink('../run.bat');

echo NL;

echo '> Copying new files ...' . NL;
rcopy('./temp/ManiaLive/libraries/ManiaLive', '../libraries/ManiaLive');
rcopy('./temp/ManiaLive/libraries/ManiaLiveApplication', '../libraries/ManiaLiveApplication');
rcopy('./temp/ManiaLive/libraries/ManiaHome', '../libraries/ManiaHome');
rcopy('./temp/ManiaLive/libraries/ManiaLib', '../libraries/ManiaLib');
copy('./temp/ManiaLive/config/config-example.ini', '../config/config-example.ini');
copy('./temp/ManiaLive/bootstrapper.php', '../bootstrapper.php');
copy('./temp/ManiaLive/changelog.txt', '../changelog.txt');
copy('./temp/ManiaLive/utils.inc.php', '../utils.inc.php');
copy('./temp/ManiaLive/LICENSE', '../LICENSE');
copy('./temp/ManiaLive/README', '../README');
copy('./temp/ManiaLive/CONVENTIONS', '../CONVENTIONS');
copy('./temp/ManiaLive/update/update.php', '../update/update.php'); // update the updater itself!
copy('./temp/ManiaLive/run', '../run');
copy('./temp/ManiaLive/run.bat', '../run.bat');

echo NL;

echo '> Cleaning up ...' . NL;

rrmdir('./temp');

echo '>> Done!' . NL;

/**
 * Recursively remove a directory.
 * @param $dir Directory to remove.
 */
function rrmdir($dir)
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
					rrmdir($dir.$obj);
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

/**
 * Recursively copy a directory.
 * @param string $src Source directory.
 * @param string $dst Destination directory.
 */
function rcopy($src, $dst)
{
	$dir = opendir($src);
	@mkdir($dst);
	while(false !== ($file = readdir($dir)))
	{
		if (($file != '.') && ($file != '..'))
		{
			if (is_dir($src . '/' . $file))
			{
				rcopy($src . '/' . $file, $dst . '/' . $file);
			}
			else
			{
				echo 'copying: ' . $src . NL;
				copy($src . '/' . $file, $dst . '/' . $file);
			}
		}
	}
	closedir($dir);
}
?>