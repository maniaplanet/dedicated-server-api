<?php
/**
 * @copyright NADEO (c) 2010
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