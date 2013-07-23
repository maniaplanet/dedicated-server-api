<?php
/**
 * Represents a Dedicated TrackMania Server Vote
 * ManiaLive - TrackMania dedicated server manager in PHP
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */
namespace DedicatedApi\Structures;

class VoteRatio extends AbstractStructure
{
	const COMMAND_SCRIPT_SETTINGS = 'SetModeScriptSettingsAndCommands';
	const COMMAND_NEXT_MAP = 'NextMap';
	const COMMAND_JUMP_MAP = 'JumpToMapIndex';
	const COMMAND_SET_NEXT_MAP = 'SetNextMapIndex';
	const COMMAND_RESTART_MAP = 'RestartMap';
	const COMMAND_TEAM_BALANCE = 'AutoTeamBalance';
	const COMMAND_KICK = 'Kick';
	const COMMAND_BAN = 'Ban';
	
	public $command;
	public $param;
	public $ratio;

	public function __construct($command = null, $ratio = null)
	{
		$this->command = $command;
		$this->ratio = $ratio;
	}
}
?>