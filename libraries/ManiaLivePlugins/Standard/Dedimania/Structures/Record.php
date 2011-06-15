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
namespace ManiaLivePlugins\Standard\Dedimania\Structures;

use ManiaLive\Utilities\Time;
use ManiaLib\Utils\TMStrings;
use ManiaLive\Utilities\Console;

class Record extends \ManiaLive\DedicatedApi\Structures\AbstractStructure
{
	public $login;
	public $nickName;
	public $best;
	public $rank;
	public $checks;
	public $vote;
	public $challenge;

	/**
	 * Do sanity checks on the record object
	 * to find faked records.
	 */
	function validate()
	{
		// all checkpoints have to be in increasing alignment
		$lastCheck = 0;
		foreach ($this->checks as $check)
		{
			if ($lastCheck >= $check)
			{
				Console::println('[Dedimania] Record failed inc checkpoint validation!');
				Console::println($this->toString());
				return false;
			}
			$lastCheck = $check;
		}

		// best time must be equal to the last checkpoint
		if ($this->best != $check)
		{
			Console::println('[Dedimania] Record failed last checkpoint validation!');
			Console::println($this->toString());
			return false;
		}

		return true;
	}

	/**
	 * Builds a string from a record that can
	 * be printed to console and is easy to read.
	 */
	function toString()
	{
		$str = array();
		$str[] = '[RecordObject #' . $this->rank . ' by ' . $this->login . ' : ' . $this->best . ']';
		foreach ($this->checks as $i => $time)
		{
			$str[] = '  [Checkpoint #' . $i . ': ' . $time . ']';
		}
		return implode(APP_NL, $str);
	}

	/**
	 * Removes cross references so that
	 * the record can be freed easily.
	 */
	function destroy()
	{
		$this->challenge = null;
	}
}

?>