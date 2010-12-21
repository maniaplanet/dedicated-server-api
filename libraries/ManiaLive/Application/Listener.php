<?php

namespace ManiaLive\Application;

interface Listener extends \ManiaLive\Event\Listener
{
	function onInit();
	function onRun();
	function onPreLoop();
	function onPostLoop();
	function onTerminate();
}