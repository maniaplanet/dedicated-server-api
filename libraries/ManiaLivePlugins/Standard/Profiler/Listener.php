<?php
/**
 * Profiler Plugin - Show statistics about ManiaLive
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\Standard\Profiler;

/**
 * Description of Listener
 */
interface Listener extends \ManiaLive\Event\Listener
{
	function onNewCpuValue($newValue);
	function onNewMemoryValue($newValue);
	function onNewNetworkValue($newValue);
}

?>