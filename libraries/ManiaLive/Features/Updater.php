<?php

namespace ManiaLive\Features;

use ManiaLive\Utilities\Console;
use ManiaLive\Event\Dispatcher;

/**
 * Checks for updates periodically.
 * @author Florian Schnell
 * @copyright 2010 NADEO
 */
class Updater extends \ManiaLive\Utilities\Singleton
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
		
		try
		{
			$version = intval(file_get_contents('http://manialink.manialive.com/public/version'));
		}
		catch(\Exception $e)
		{
			if (strstr($e->getMessage(), 'failed to open stream') === false)
				throw $e;
		}
		
		if ($version > \ManiaLiveApplication\Version)
		{
			Console::println('###############################################################################');
			Console::println('#                                                                             #');
			Console::println('#                  A new version of ManiaLive is available!                   #');
			Console::println('#                    An update is strongly recommended!                       #');
			Console::println('#                     Download it at www.manialive.com                        #');
			Console::println('#                                                                             #');
			Console::println('###############################################################################');
		}
	}
}

?>