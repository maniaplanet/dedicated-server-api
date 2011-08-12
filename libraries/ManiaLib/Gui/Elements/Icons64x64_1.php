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
 * \ManiaLib\Gui\Elements\Icons64x64_1 quad
 */	
class Icons64x64_1 extends \ManiaLib\Gui\Elements\Icon
{
	/**#@+
	 * @ignore
	 */
	protected $style = \ManiaLib\Gui\Elements\Quad::Icons64x64_1;
	protected $subStyle = self::GenericButton;
	/**#@-*/
	
	const Stereo3D                    = '3DStereo';
	const Add                         = 'Add';
	const Arrow                       = 'Arrow';
	const ArrowBlue                   = 'ArrowBlue';
	const ArrowDown                   = 'ArrowDown';
	const ArrowFastNext               = 'ArrowFastNext';
	const ArrowFastPrev               = 'ArrowFastPrev';
	const ArrowFirst                  = 'ArrowFirst';
	const ArrowGreen                  = 'ArrowGreen';
	const ArrowLast                   = 'ArrowLast';
	const ArrowPrev                   = 'ArrowPrev';
	const ArrowNext                   = 'ArrowNext';
	const ArrowRed                    = 'ArrowRed';
	const ArrowUp                     = 'ArrowUp';
	const Browser                     = 'Browser';
	const Buddy                       = 'Buddy';
	const ButtonLeagues               = 'ButtonLeagues';
	const Camera                      = 'Camera';
	const Check                       = 'Check';
	const ClipPause                   = 'ClipPause';
	const ClipPlay                    = 'ClipPlay';
	const ClipRewind                  = 'ClipRewind';
	const Close                       = 'Close';
	const EmptyIcon                   = 'Empty';
	const Finish                      = 'Finish';
	const FinishGrey                  = 'FinishGrey';
	const First                       = 'First';
	const GenericButton               = 'GenericButton';
	const Green                       = 'Green';
	const IconLeaguesLadder           = 'IconLeaguesLadder';
	const IconPlayers                 = 'IconPlayers';
	const IconPlayersLadder           = 'IconPlayersLadder';
	const IconServers                 = 'IconServers';
	const Inbox                       = 'Inbox';
	const LvlGreen                    = 'LvlGreen';
	const LvlRed                      = 'LvlRed';
	const LvlYellow                   = 'LvlYellow';
	const ManiaLinkNext               = 'ManiaLinkNext';
	const ManiaLinkPrev               = 'ManiaLinkPrev';
	const Maximize                    = 'Maximize';
	const NewMessage                  = 'NewMessage';
	const NotBuddy                    = 'NotBuddy';
	const OfficialRace                = 'OfficialRace';
	const Opponents                   = 'Opponents';
	const Outbox                      = 'Outbox';
	const QuitRace                    = 'QuitRace';
	const RedHigh                     = 'RedHigh';
	const RedLow                      = 'RedLow';
	const Refresh                     = 'Refresh';
	const RestartRace                 = 'RestartRace';
	const Save                        = 'Save';
	const Second                      = 'Second';
	const ShowDown                    = 'ShowDown';
	const ShowDown2                   = 'ShowDown2';
	const ShowLeft                    = 'ShowLeft';
	const ShowLeft2                   = 'ShowLeft2';
	const ShowRight                   = 'ShowRight';
	const ShowRight2                  = 'ShowRight2';
	const ShowUp                      = 'ShowUp';
	const ShowUp2                     = 'ShowUp2';
	const SliderCursor                = 'SliderCursor';
	const SliderCursor2               = 'SliderCursor2';
	const StateFavourite              = 'StateFavourite';
	const StatePrivate                = 'StatePrivate';
	const StateSuggested              = 'StateSuggested';
	const Sub                         = 'Sub';
	const TV                          = 'TV';
	const TagTypeBronze               = 'TagTypeBronze';
	const TagTypeGold                 = 'TagTypeGold';
	const TagTypeNadeo                = 'TagTypeNadeo';
	const TagTypeNone                 = 'TagTypeNone';
	const TagTypeSilver               = 'TagTypeSilver';
	const Third                       = 'Third';
	const ToolRoot                    = 'ToolRoot';
	const ToolTree                    = 'ToolTree';
	const ToolUp                      = 'ToolUp';
	const TrackInfo                   = 'TrackInfo';
	const YellowHigh                  = 'YellowHigh';
	const YellowLow                   = 'YellowLow';
	/**
	 * Following substyles will maybe disappear
	 */
	const CameraLocal                 = 'CameraLocal';
	const MediaAudioDownloading       = 'MediaAudioDownloading';
	const MediaPlay                   = 'MediaPlay';
	const MediaStop                   = 'MediaStop';
	const MediaVideoDownloading       = 'MediaVideoDownloading';
	const ToolLeague1                 = 'ToolLeague1';
	
	function __construct($size = 4)
	{
		parent::__construct($size);
	}
}

?>