<?php

namespace ManiaLive\Gui\Displayables;

class Blank implements \ManiaLive\Gui\Handler\Displayable
{
	public $id;
	
	function __construct($id = null) { $this->id = $id; }
	function display($login) {}
	function getId() { return $this->id; }
	function getPosX() {}
	function getPosY() {}
	function getPosZ() {}
	function hide($login) {}
}