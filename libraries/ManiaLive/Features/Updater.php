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
class Updater extends \ManiaLive\Utilities\Singleton
implements \ManiaLive\Features\Tick\Listener
{
	protected $lastDisplayed;
	/**
	 * @var \ManiaLive\PluginHandler\PluginHandler
	 */
	protected $pluginHandler;
	
	protected function __construct()
	{
		$this->lastDisplayed = 0;
		$this->pluginHandler = PluginHandler::getInstance();
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
		$client = new \ManiaLib\Rest\Client();
		$client->setAPIURL(APP_API);
		$response = $client->execute('GET', '/manialive/version/check/' . \ManiaLiveApplication\Version . '/index.json');
		$newManiaLive = $response->update;
		
		// check for plugin updates 
		$this->pluginHandler->refreshRepositoryInfo();
		$entries = $this->pluginHandler->getRepositoryEntries();
		$updates = array();
		foreach ($entries as $entry)
		{
			foreach ($entry->plugins as $id => $version)
			{
				if ($version < $entry->version)
				{
					$updates[$entry->id] = $entry;
				}
			}
		}
		$newPlugins = count($updates);
		
		// display message in console
		if ($newPlugins || $newManiaLive)
		{
			Console::println(str_repeat('#', 79));
			
			if ($newManiaLive)
			{
				$days = ceil((time() - strtotime($response->version->date)) / 86400);
				
				Console::println('#' . str_repeat(' ', 77) . '#');
				Console::println(str_pad("#           A new version of ManiaLive is available since $days day(s)!", 78) . "#");
				Console::println(str_pad("#                      An update is strongly recommended!", 78) . "#");
			}
			
			if ($newPlugins)
			{
				Console::println('#' . str_repeat(' ', 77) . '#');
				Console::println(str_pad("#                The plugin repository contains $newPlugins new update(s)!", 78) . "#");
			}
			
			foreach ($updates as $entry)
			{
				Console::println('#' . str_repeat(' ', 77) . '#');
				Console::println(str_pad("# {$entry->name} published by {$entry->author}", 78) . "#");
				Console::println('# ' . str_repeat('-', 75) . " #");
				Console::println(str_pad("#   download: {$entry->urlDownload}", 78) . "#");
				Console::println(str_pad("#   info:     {$entry->urlInfo}", 78) . "#");
				$i = 0;
				foreach ($entry->plugins as $id => $version)
				{
					$mark = '-';
					if ($version < $entry->version)
					{
						$mark = '+';
					}
					if ($i++ == 0)
					{
						Console::println(str_pad("#   plugins:  $mark $id", 78) . "#");
					}
					else
					{
						Console::println(str_pad("#             $mark $id", 78) . "#");
					}
					
				}
			}
			
			Console::println('#' . str_repeat(' ', 77) . '#');
			Console::println(str_repeat('#', 79));
		}
	}
}

?>