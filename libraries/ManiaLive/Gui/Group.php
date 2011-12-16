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
 * Description of Group
 */
class Group implements \Iterator
{
	private static $groups = array();
	
	private $internalName;
	private $logins = array();
	
	static function Create($publicName, $logins=array())
	{
		if(isset(self::$groups[$publicName]))
		{
			$group = self::$groups[$publicName];
			foreach($logins as $login)
				$group->add($login);
			return $group;
		}
		
		$group = new Group($publicName, $logins);
		self::$groups[$publicName] = $group;
		return $group;
	}
	
	static function Get($publicName)
	{
		if(isset(self::$groups[$publicName]))
			return self::$groups[$publicName];
		return null;
	}
	
	static function Erase($publicName)
	{
		if(isset(self::$groups[$publicName]))
		{
			$group = self::$groups[$publicName];
			Window::Erase($group);
			unset(self::$groups[$publicName]);
		}
	}
	
	protected function __construct($publicName, $logins)
	{
		$this->internalName = '$'.$publicName;
		foreach($logins as $login)
			$this->logins[$login] = $login;
	}
	
	function __toString()
	{
		return $this->internalName;
	}
	
	function add($login, $showWindows=false)
	{
		if(!isset($this->logins[$login]))
		{
			$this->logins[$login] = $login;
			if($showWindows)
				foreach(Window::Get($this) as $window)
					if($window->isVisible())
						$window->show($login);
		}
	}
	
	function contains($login)
	{
		return isset($this->logins[$login]);
	}
	
	function count()
	{
		return count($this->logins);
	}
	
	function remove($login)
	{
		if(isset($this->logins[$login]))
		{
			unset($this->logins[$login]);
			foreach(Window::Get($this) as $window)
				$window->hide($login);
		}
	}
	
	function toArray()
	{
		return $this->logins;
	}

	// #Iterator implementation
	public function current()
	{
		return current($this->logins);
	}

	public function key()
	{
		return key($this->logins);
	}

	public function next()
	{
		next($this->logins);
	}

	public function rewind()
	{
		reset($this->logins);
	}

	public function valid()
	{
		return key($this->logins) !== null;
	}
}

?>