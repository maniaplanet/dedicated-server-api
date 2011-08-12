<?php
/**
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLib\Gui\Elements;

/**
 * Copilot icons
 */
class Copilot extends Icon
{
	/**#@+
	 * @ignore
	 */
	protected $style = \ManiaLib\Gui\Elements\Quad::Copilot;
	protected $subStyle = self::Down;
	/**#@-*/
	
	const Down       = 'Down';
	const DownGood   = 'DownGood';
	const DownWrong  = 'DownWrong';
	const Left       = 'Left';
	const LeftGood   = 'LeftGood';
	const LeftWrong  = 'LeftWrong';
	const Right      = 'Right';
	const RightGood  = 'RightGood';
	const RightWrong = 'RightWrong';
	const Up         = 'Up';
	const UpGood     = 'UpGood';
	const UpWrong    = 'UpWrong';
}

?>