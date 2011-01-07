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
	function __construct($source)
	{
		parent::__construct($source);
	}
	
	function fireDo($listener)
	{
		$listener->onPluginLoaded($this->source);
	}
}
?>