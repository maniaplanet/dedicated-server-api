<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\Gui\Windowing\Controls;

/**
 * Extend this to build your own tabs,
 * that you can later add to the tabview component.
 * 
 * @author Florian Schnell
 */
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