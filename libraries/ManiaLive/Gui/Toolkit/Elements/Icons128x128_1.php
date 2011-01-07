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

/**
 * Icons128x128_1 quad
 */
class Icons128x128_1 extends Quad
{
	protected $style = Quad::Icons128x128_1;
	protected $subStyle = self::Forever;

	const Advanced                    = 'Advanced';
	const Back                        = 'Back';
	const BackFocusable               = 'BackFocusable';
	const Beginner                    = 'Beginner';
	const Browse                      = 'Browse';
	const Buddies                     = 'Buddies';
	const Challenge                   = 'Challenge';
	const ChallengeAuthor             = 'ChallengeAuthor';
	const Coppers                     = 'Coppers';
	const Create                      = 'Create';
	const Credits                     = 'Credits';
	const Custom                      = 'Custom';
	const CustomStars                 = 'CustomStars';
	const DefaultIcon                 = 'Default';
	const Download                    = 'Download';
	const Easy                        = 'Easy';
	const Editor                      = 'Editor';
	const Extreme                     = 'Extreme';
	const Forever                     = 'Forever';
	const GhostEditor                 = 'GhostEditor';
	const Hard                        = 'Hard';
	const Hotseat                     = 'Hotseat';
	const Inputs                      = 'Inputs';
	const Invite                      = 'Invite';
	const LadderPoints                = 'LadderPoints';
	const Lan                         = 'Lan';
	const Launch                      = 'Launch';
	const Load                        = 'Load';
	const LoadTrack                   = 'LoadTrack';
	const ManiaZones                  = 'ManiaZones';
	const Manialink                   = 'Manialink';
	const MedalCount                  = 'MedalCount';
	const MediaTracker                = 'MediaTracker';
	const Medium                      = 'Medium';
	const Multiplayer                 = 'Multiplayer';
	const Nations                     = 'Nations';
	const NewTrack                    = 'NewTrack';
	const Options                     = 'Options';
	const Padlock                     = 'Padlock';
	const Paint                       = 'Paint';
	const Platform                    = 'Platform';
	const PlayerPage                  = 'PlayerPage';
	const Profile                     = 'Profile';
	const ProfileAdvanced             = 'ProfileAdvanced';
	const ProfileVehicle              = 'ProfileVehicle';
	const Puzzle                      = 'Puzzle';
	const Quit                        = 'Quit';
	const Race                        = 'Race';
	const Rankings                    = 'Rankings';
	const Rankinks                    = 'Rankinks';
	const Replay                      = 'Replay';
	const Save                        = 'Save';
	const ServersAll                  = 'ServersAll';
	const ServersFavorites            = 'ServersFavorites';
	const ServersSuggested            = 'ServersSuggested';
	const Share                       = 'Share';
	const ShareBlink                  = 'ShareBlink';
	const SkillPoints                 = 'SkillPoints';
	const Solo                        = 'Solo';
	const Statistics                  = 'Statistics';
	const Stunts                      = 'Stunts';
	const United                      = 'United';
	const Upload                      = 'Upload';
	const Vehicles                    = 'Vehicles';

	function __construct($size = 7)
	{
		$this->sizeX = $size;
		$this->sizeY = $size;
	}
}

?>