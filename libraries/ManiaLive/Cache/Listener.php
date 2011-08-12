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

interface Listener extends \ManiaLive\Event\Listener
{
	/**
	 * New value is stored in the cache.
	 * @param \ManiaLive\Cache\Entry $entry
	 */
	function onStore(Entry $entry);
	
	/**
	 * Value is removed from the cache.
	 * @param \ManiaLive\Cache\Entry $entry
	 */
	function onEvict(Entry $entry);
}

?>