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
	const ON_STORE = 0;
	const ON_EVICT = 1;
	
	protected $onWhat;
	protected $params;
	
	function __construct($onWhat, $params = array())
	{
		$this->onWhat = $onWhat;
		$this->params = $params;
	}
	
	function fireDo($listener)
	{
		switch ($this->onWhat)
		{
			case self::ON_STORE:
				call_user_func_array(array($listener, 'onStore'), $this->params);
				break;
			case self::ON_EVICT:
				call_user_func_array(array($listener, 'onEvict'), $this->params);
				break;
		}
	}
}

?>