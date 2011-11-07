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

namespace ManiaLive\Features;

use ManiaLive\Event\Dispatcher;
use ManiaLive\Features\Tick\Listener as TickListener;
use ManiaLive\Features\Tick\Event as TickEvent;
use ManiaLive\Utilities\Console;

/**
 * Checks for updates periodically.
 *
 * @author Florian Schnell
 */
class Updater extends \ManiaLib\Utils\Singleton implements TickListener
{
	protected $lastDisplayed;
	
	protected function __construct()
	{
		$this->lastDisplayed = 0;
		Dispatcher::register(TickEvent::getClass(), $this);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Features/Tick/ManiaLive\Features\Tick.Listener::onTick()
	 */
	function onTick()
	{
		if ($this->lastDisplayed < time())
		{
			$this->checkUpdate();
			$this->lastDisplayed = time() + 3600;
		}
	}
	
	/**
	 * Routine that compares version number to
	 * the latest version online.
	 */
	function checkUpdate()
	{
		try
		{
			$client = new UpdaterClient();
			$response = $client->checkVersion(\ManiaLiveApplication\Version);
		}
		catch (\Exception $e)
		{
			Console::println('ERROR: Update service was unable to contact server ...');
			return;
		}
		
		// display message in console
		if ($response->update)
		{
			$days = ceil((time() - strtotime($response->version->date)) / 86400);
			Console::println(str_repeat('#', 79));
			Console::println('#' . str_repeat(' ', 77) . '#');
			Console::println(str_pad("#           A new version of ManiaLive is available since $days day(s)!", 78) . "#");
			Console::println(str_pad("#                      An update is strongly recommended!", 78) . "#");
			Console::println('#' . str_repeat(' ', 77) . '#');
			Console::println(str_repeat('#', 79));
		}
	}
}

class UpdaterClient extends \Maniaplanet\WebServices\HTTPClient
{
	protected $APIURL = APP_API;
	
	function checkVersion($version)
	{
		return $this->execute('GET', '/manialive/version/check/'.$version.'/index.json');
	}
}

?>