<?php 
/**
 * ManiaLib - Lightweight PHP framework for Manialinks
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLib\Utils;

/**
 * @deprecated
 */
abstract class Debug
{
	/**
	 * @deprecated
	 */
	static function isDebug()
	{
		return \ManiaLib\Config\Config::getInstance()->debug;
	}
}

?>