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

namespace ManiaLive\Gui\Windowing\Windows;

use ManiaLive\Utilities\Console;
use ManiaLib\Gui\Elements\Quad;

class Shortkey extends \ManiaLive\Gui\Windowing\Window
{
	protected $onKey = array();
	
	const F5 = 1;
	const F6 = 2;
	const F7 = 3;
	
	function initializeComponents()
	{
		$this->onKey = array(
			self::F5 => array(),
			self::F6 => array(),
			self::F7 => array()
		);
		
		$ui = new Quad();
		$ui->setPosition(100, 100);
		$ui->setStyle(null);
		$ui->setActionKey(self::F5);
		$ui->setAction($this->callback('pressKey', self::F5));
		$this->addComponent($ui);
		
		$ui = new Quad();
		$ui->setPosition(100, 100);
		$ui->setStyle(null);
		$ui->setActionKey(self::F6);
		$ui->setAction($this->callback('pressKey', self::F6));
		$this->addComponent($ui);
		
		$ui = new Quad();
		$ui->setPosition(100, 100);
		$ui->setStyle(null);
		$ui->setActionKey(self::F7);
		$ui->setAction($this->callback('pressKey', self::F7));
		$this->addComponent($ui);
	}
	
	function addCallback($key, $callback)
	{
		if (isset($this->onKey[$key]))
		{
			$this->onKey[$key][] = $callback;
		}
	}
	
	function pressKey($login, $key)
	{
		if (isset($this->onKey[$key]))
		{
			foreach ($this->onKey[$key] as $callback)
			{
				if (is_callable($callback))
				{
					call_user_func($callback, $login);
				}
			}
		}
	}
}