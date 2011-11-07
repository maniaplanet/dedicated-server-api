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

namespace ManiaLive\Gui;

/**
 * Set the custom appearance for the game UI here.
 */
final class CustomUI
{
	const NOTICE          = 1;
	const CHALLENGE_INFO  = 2;
	const CHAT            = 4;
	const CHECKPOINT_LIST = 8;
	const ROUND_SCORES    = 16;
	const SCORETABLE      = 32;
	const GLOBAL_UI       = 64;
	const ALL             = 127;
	
	private static $instances = array();
	private static $fieldNames = array(
		self::NOTICE => 'notice',
		self::CHALLENGE_INFO => 'challenge_info',
		self::CHAT => 'chat',
		self::CHECKPOINT_LIST => 'checkpoint_list',
		self::ROUND_SCORES => 'round_scores',
		self::SCORETABLE => 'scoretable',
		self::GLOBAL_UI => 'global'
	);
	
	private $currentState = self::ALL;
	private $nextState = self::ALL;
	
	private function __construct() {}
	
	static function Create($login)
	{
		if(!isset(self::$instances[$login]))
			self::$instances[$login] = new self();
		return self::$instances[$login];
	}
	
	static function GetAll()
	{
		return self::$instances;
	}
	
	static function Erase($login)
	{
		unset(self::$instances[$login]);
	}
	
	static function ShowForAll($fields)
	{
		$fields &= self::ALL;
		foreach(self::$instances as $customUI)
			$customUI->nextState |= $fields;
	}
	
	static function HideForAll($fields)
	{
		foreach(self::$instances as $customUI)
			$customUI->nextState &= ~$fields;
	}
	
	function show($fields)
	{
		$this->nextState |= ($fields & self::ALL);
	}
	
	function hide($fields)
	{
		$this->nextState &= ~$fields;
	}
	
	function getDiff()
	{
		return $this->currentState ^ $this->nextState;
	}
	
	function save()
	{
		if($this->nextState == $this->currentState)
			return;
		
		Manialinks::beginCustomUi();
		$diff = $this->currentState ^ $this->nextState;
		foreach(self::$fieldNames as $field => $name)
			if($diff & $field)
				Manialinks::setVisibility($name, (bool)($this->nextState & $field));
		Manialinks::endCustomUi();
		
		$this->hasBeenSaved();
	}
	
	function saveToDefault()
	{
		$oldNextState = $this->nextState;
		$this->nextState = self::ALL;
		$this->save();
		$this->nextState = $oldNextState;
	}
	
	function hasBeenSaved()
	{
		$this->currentState = $this->nextState;
	}
}
?>