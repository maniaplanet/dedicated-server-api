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

class Vote extends AbstractStructure
{
	const STATE_NEW = 'NewVote';
	const STATE_CANCELLED = 'VoteCancelled';
	const STATE_PASSED = 'VotePassed';
	const STATE_FAILED = 'VoteFailed';
	
	public $status;
	public $callerLogin;
	public $cmdName;
	public $cmdParam;
}
?>