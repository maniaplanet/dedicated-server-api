<?php

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