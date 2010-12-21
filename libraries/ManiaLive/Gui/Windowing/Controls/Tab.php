<?php

namespace ManiaLive\Gui\Windowing\Controls;

abstract class Tab extends \ManiaLive\Gui\Windowing\Control
{
	protected $title;
	
	function setTitle($title)
	{
		$this->title = $title;
	}
	
	function getTitle()
	{
		return $this->title;
	}
	
	function onActivate() {}
	
	function onDeactivate() {}
}

?>