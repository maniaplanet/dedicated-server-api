<?php
/**
 * ChatLogger Plugin - Save everything typed in the chat in a file
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\Standard\ChatLogger;

/**
 * Description of Config
 */
class Config extends \ManiaLib\Utils\Singleton
{
	public $logFilename = 'ChatLog';
}

?>