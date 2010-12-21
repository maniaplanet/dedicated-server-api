<?php

namespace ManiaLive\Gui\Toolkit\Elements;

/**
 * BgsChallengeMedals quad
 */	
class BgsChallengeMedals extends Quad
{
	protected $style = Quad::BgsChallengeMedals;
	protected $subStyle = self::BgBronze;
	
	const BgBronze                    = 'BgBronze';
	const BgGold                      = 'BgGold';
	const BgNadeo                     = 'BgNadeo';
	const BgNotPlayed                 = 'BgNotPlayed';
	const BgPlayed                    = 'BgPlayed';
	const BgSilver                    = 'BgSilver';
}