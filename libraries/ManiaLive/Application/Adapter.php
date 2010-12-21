<?php

namespace ManiaLive\Application;

abstract class Adapter implements Listener
{
	function onInit(){}
	function onRun(){}
	function onPreLoop(){}
	function onPostLoop(){}
	function onTerminate(){}
}

?>