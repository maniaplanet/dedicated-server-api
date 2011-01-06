<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\Application;

interface Listener extends \ManiaLive\Event\Listener
{
	function onInit();
	function onRun();
	function onPreLoop();
	function onPostLoop();
	function onTerminate();
}