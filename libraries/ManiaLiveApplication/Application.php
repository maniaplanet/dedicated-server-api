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

namespace ManiaLiveApplication;

use ManiaLive\Features\Updater;

const Version = '3.0.0';

class Application extends \ManiaLive\Application\AbstractApplication
{
	protected function init()
	{
		\ManiaLive\Utilities\Console::println('Running ManiaLive '.Version.'...');
		parent::init();

		if(!\ManiaLive\Config\Config::getInstance()->lanMode)
			Updater::getInstance();
	}
}

?>