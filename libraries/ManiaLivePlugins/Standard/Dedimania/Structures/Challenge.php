<?php

namespace ManiaLivePlugins\Standard\Dedimania\Structures;

use ManiaLivePlugins\Standard\Dedimania\Structures\Record;

class Challenge extends \ManiaLive\DedicatedApi\Structures\AbstractStructure
{
	public $uid;
	public $totalRaces;
	public $totalPlayers;
	public $timeAttackRaces;
	public $timeAttackPlayers;
	public $numberOfChecks;
	public $serverMaxRecords;
	public $records;
	
	static public function fromArray($array)
	{
		$challenge = parent::fromArray($array);
		
		// parse records into objects ...
		if (isset($array['Records']))
		{
			$challenge->records = array();
			$records = $array['Records'];
			foreach ($records as $record)
			{
				// create record object
				$record = Record::fromArray($record);
				
				// link challenge to record
				$record->challenge = $challenge;
				
				// link record to challenge
				$challenge->records[] = $record;
				
				// validate the challenge object
				$record->validate();
			}
		}
		
		return $challenge;
	}
	
	/**
	 * Removes linkage between challenge and record
	 * that way resources can be freed.
	 */
	function destroy()
	{
		foreach ($this->records as $record)
		{
			$record->challenge = null;
		}
		$records = array();
	}
}

?>