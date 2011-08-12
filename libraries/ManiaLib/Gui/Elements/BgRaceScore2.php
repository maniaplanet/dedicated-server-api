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
 * BgRaceScore2 quad
 */	
class BgRaceScore2 extends \ManiaLib\Gui\Elements\Quad
{
	/**#@+
	 * @ignore
	 */
	protected $style = \ManiaLib\Gui\Elements\Quad::BgRaceScore2;
	protected $subStyle = self::BgCardServer;
	/**#@-*/
	
	const BgCardServer                = 'BgCardServer';
	const BgScores                    = 'BgScores';
	const CupFinisher                 = 'CupFinisher';
	const CupPotentialFinisher        = 'CupPotentialFinisher';
	const Fame                        = 'Fame';
	const Handle                      = 'Handle';
	const HandleBlue                  = 'HandleBlue';
	const HandleRed                   = 'HandleRed';
	const IsLadderDisabled            = 'IsLadderDisabled';
	const IsLocalPlayer               = 'IsLocalPlayer';
	const LadderRank                  = 'LadderRank';
	const Laps                        = 'Laps';
	const Podium                      = 'Podium';
	const Points                      = 'Points';
	const SandTimer                   = 'SandTimer';
	const ScoreLink                   = 'ScoreLink';
	const ScoreReplay                 = 'ScoreReplay';
	const SendScore                   = 'SendScore';
	const Speaking                    = 'Speaking';
	const Spectator                   = 'Spectator';
	const Tv                          = 'Tv';
	const Warmup                      = 'Warmup';
	const Cartouche                   = 'Cartouche';
	const CartoucheLine               = 'CartoucheLine';
}

?>