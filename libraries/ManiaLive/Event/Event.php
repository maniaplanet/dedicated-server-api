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

namespace ManiaLive\Event;

abstract class Event
{
	const ALL = 0xFFFFFFFF;
	
	protected $onWhat;
	
	final static function getClass()
	{
		return get_called_class();
	}
	
	function __construct($onWhat)
	{
		$this->onWhat = $onWhat;
	}
	
	final function getMethod()
	{
		return $this->onWhat;
	}
	
	abstract function fireDo($listener);
}

?>