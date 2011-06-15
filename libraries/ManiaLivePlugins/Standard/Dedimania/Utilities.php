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
namespace ManiaLivePlugins\Standard\Dedimania;

class Utilities
{
	static function parseGame($version)
	{
		switch ($version)
		{
			case 'TmUnitedForever':
				return 'TMUF';

			case 'TmForever':
				return 'TMF';

			case 'TmUnited.':
			case 'TmUnited':
				return 'TMU';

			case 'TmNationsESWC':
				return 'TMN';

			case 'TmSunrise':
				return 'TMS';

			case 'TmOriginal':
				return 'TMO';

			default:
				return 'Unknown';
	    }

	    return $version;
	}
}

?>