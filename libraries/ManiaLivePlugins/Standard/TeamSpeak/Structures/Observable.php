<?php
/**
 * TeamSpeak Plugin - Connect to a TeamSpeak 3 server
 * Original work by refreshfr
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\Standard\TeamSpeak\Structures;

/**
 * Description of Observable
 */
abstract class Observable
{
	private $observers = array();
	
	function addObserver(Observer $observer)
	{
		$this->observers[spl_object_hash($observer)] = $observer;
	}
	
	function removeObserver(Observer $observer)
	{
		unset($this->observers[spl_object_hash($observer)]);
	}
	
	function removeAllObservers()
	{
		$this->observers = array();
	}
	
	function notifyObservers()
	{
		foreach($this->observers as $observer)
			$observer->onUpdate();
	}
}

?>