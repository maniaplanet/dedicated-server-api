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

namespace ManiaLive\Cache;

class Event extends \ManiaLive\Event\Event
{
	const ON_STORE = 1;
	const ON_EVICT = 2;
	
	protected $entry;
	
	function __construct($onWhat, $entry)
	{
		parent::__construct($onWhat);
		$this->entry = $entry;
	}
	
	function fireDo($listener)
	{
		switch($this->onWhat)
		{
			case self::ON_STORE: $listener->onStore($this->entry); break;
			case self::ON_EVICT: $listener->onEvict($this->entry); break;
		}
	}
}

?>