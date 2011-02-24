<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 2453 $:
 * @author      $Author: florian $:
 * @date        $Date: 2011-02-17 19:02:35 +0100 (jeu., 17 févr. 2011) $:
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