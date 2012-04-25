<?php
/**
 * AutoQueue plugin - Manage a queue of spectators
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\Standard\AutoQueue;

/**
 * Description of Config
 */
class Config extends \ManiaLib\Utils\Singleton
{
	public $lastToKick = 2;
	public $queueInsteadOfKick = true;
	public $ignoreAdmins = false;
	public $allowTrueSpectators = false;
	
	public $playerIdleKick = 300;
	public $spectatorIdleKick = 600;
	
	public $posX = 0;
	public $posY = -40;
}

?>
