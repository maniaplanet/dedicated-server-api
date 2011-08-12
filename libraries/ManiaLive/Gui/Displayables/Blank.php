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