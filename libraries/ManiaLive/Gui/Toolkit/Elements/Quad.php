<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 1709 $:
 * @author      $Author: svn $:
 * @date        $Date: 2011-01-07 14:06:13 +0100 (ven., 07 janv. 2011) $:
 */

namespace ManiaLive\Gui\Toolkit\Elements;

use ManiaLive\Gui\Toolkit\DefaultStyles;

/**
 * Quad
 */
class Quad extends Element
{
	/**#@+
	 * Manialink <b>style</b> for the <b>Quad</b> element 
	 */
	const BgRaceScore2        = 'BgRaceScore2';
	const Bgs1                = 'Bgs1';
	const Bgs1InRace          = 'Bgs1InRace';
	const BgsChallengeMedals  = 'BgsChallengeMedals';
	const BgsPlayerCard       = 'BgsPlayerCard';
	const Icons128x128_1      = 'Icons128x128_1';
	const Icons128x32_1       = 'Icons128x32_1';
	const Icons64x64_1        = 'Icons64x64_1';
	const MedalsBig           = 'MedalsBig';
	/**#@-*/
	
	protected $xmlTagName = 'quad';
	protected $style = DefaultStyles::Quad_Style;
	protected $subStyle = DefaultStyles::Quad_Substyle;
}

?>