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

const Version = 1830;

if (extension_loaded('pcntl'))
	declare(ticks = 1); 

class Application extends \ManiaLive\Application\AbstractApplication
{
	function __construct()
	{
		if (extension_loaded('pcntl'))
		{
			pcntl_signal(SIGTERM, array($this, 'kill'));  
			pcntl_signal(SIGINT, array($this, 'kill'));
		}
		
		parent::__construct();
	}
	
	protected function init()
	{
		parent::init();
		Updater::getInstance();
	}
}

?>