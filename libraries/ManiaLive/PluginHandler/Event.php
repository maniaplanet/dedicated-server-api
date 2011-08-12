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

namespace ManiaLive\PluginHandler;

/**
 * @author Florian Schnell
 */
class Event extends \ManiaLive\Event\Event
{
	const ON_PLUGIN_LOADED = 1;
	const ON_PLUGIN_UNLOADED = 2;
	
	protected $onWhat;
	
	function __construct($source, $onWhat)
	{
		parent::__construct($source);
		$this->onWhat = $onWhat;
	}
	
	function fireDo($listener)
	{
		$method = null;
		
		switch($this->onWhat)
		{
			case self::ON_PLUGIN_LOADED: $method = 'onPluginLoaded'; break;
			case self::ON_PLUGIN_UNLOADED: $method = 'onPluginUnloaded'; break;
		}
		
		if ($method != null)
			call_user_func_array(array($listener, $method), array($this->source));
	}
}
?>