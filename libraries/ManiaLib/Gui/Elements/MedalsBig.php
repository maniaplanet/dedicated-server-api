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

namespace ManiaLib\Gui\Elements;

/**
 * MedalsBig quad
 */	
class MedalsBig extends \ManiaLib\Gui\Elements\Icon
{
	/**#@+
	 * @ignore
	 */
	protected $style = \ManiaLib\Gui\Elements\Quad::MedalsBig;
	protected $subStyle = self::MedalBronze;
	/**#@-*/
	
	const MedalBronze                 = 'MedalBronze';
	const MedalGold                   = 'MedalGold';
	const MedalGoldPerspective        = 'MedalGoldPerspective';
	const MedalNadeo                  = 'MedalNadeo';
	const MedalNadeoPerspective       = 'MedalNadeoPerspective';
	const MedalSilver                 = 'MedalSilver';
	const MedalSlot                   = 'MedalSlot';
}

?>