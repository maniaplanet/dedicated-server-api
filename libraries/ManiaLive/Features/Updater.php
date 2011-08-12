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

use ManiaLive\PluginHandler\PluginHandler;
use ManiaLive\Utilities\Console;
use ManiaLive\Event\Dispatcher;

/**
 * Checks for updates periodically.
 *
 * @author Florian Schnell
 */
class Updater extends \ManiaLib\Utils\Singleton
implements \ManiaLive\Features\Tick\Listener
{
	protected $lastDisplayed;
	
	protected function __construct()
	{
		$this->lastDisplayed = 0;
		Dispatcher::register(\ManiaLive\Features\Tick\Event::getClass(), $this);
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
		$version = 0;
		
		// check for manialive update
		$newManiaLive = false;
		try
		{
			$client = new \ManiaLib\Rest\Client();
			$client->setAPIURL(APP_API);
			$response = $client->execute('GET', '/manialive/version/check/' . \ManiaLiveApplication\Version . '/index.json');
			$newManiaLive = $response->update;
		}
		catch (\Exception $e)
		{
			Console::println('ERROR: Update service was unable to contact server ...');
		}
		
		// display message in console
		if ($newManiaLive)
		{
			Console::println(str_repeat('#', 79));
			
			if ($newManiaLive)
			{
				$days = ceil((time() - strtotime($response->version->date)) / 86400);
				
				Console::println('#' . str_repeat(' ', 77) . '#');
				Console::println(str_pad("#           A new version of ManiaLive is available since $days day(s)!", 78) . "#");
				Console::println(str_pad("#                      An update is strongly recommended!", 78) . "#");
			}
			
			Console::println('#' . str_repeat(' ', 77) . '#');
			Console::println(str_repeat('#', 79));
		}
	}
}

?>