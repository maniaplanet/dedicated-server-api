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

namespace ManiaLive\Features\Tick;

class Event extends \ManiaLive\Event\Event
{
	const ON_TICK = 1;
	
	function __construct($onWhat = self::ON_TICK)
	{
		parent::__construct($onWhat);		
	}
	
	function fireDo($listener)
	{
		$listener->onTick();
	}
}

?>