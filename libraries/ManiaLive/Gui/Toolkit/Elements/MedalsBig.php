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

namespace ManiaLive\Gui\Toolkit\Elements;

/**
 * MedalsBig quad
 */
class MedalsBig extends Icon128x128_1
{
	protected $style = Quad::MedalsBig;
	protected $subStyle = self::MedalBronze;

	const MedalBronze                 = 'MedalBronze';
	const MedalGold                   = 'MedalGold';
	const MedalGoldPerspective        = 'MedalGoldPerspective';
	const MedalNadeo                  = 'MedalNadeo';
	const MedalNadeoPerspective       = 'MedalNadeoPerspective';
	const MedalSilver                 = 'MedalSilver';
	const MedalSlot                   = 'MedalSlot';
}

?>