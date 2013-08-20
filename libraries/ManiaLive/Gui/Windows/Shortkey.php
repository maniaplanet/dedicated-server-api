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

namespace ManiaLive\Gui\Windows;

use ManiaLib\Gui\Elements\Quad;

final class Shortkey extends \ManiaLive\Gui\Window
{
	private $onKey = array();
	
	const F5 = 1;
	const F6 = 2;
	const F7 = 3;
	const F8 = 4;
	
	protected function onConstruct()
	{
		$this->onKey = array(
			self::F5 => null,
			self::F6 => null,
			self::F7 => null,
			self::F8 => null
		);
		
		$ui = new Quad();
		$ui->setPosition(400, 400);
		$ui->setStyle(null);
		$ui->setActionKey(self::F5);
		$ui->setAction($this->createAction(array($this, 'onKey'), self::F5));
		$this->addComponent($ui);
		
		$ui = new Quad();
		$ui->setPosition(400, 400);
		$ui->setStyle(null);
		$ui->setActionKey(self::F6);
		$ui->setAction($this->createAction(array($this, 'onKey'), self::F6));
		$this->addComponent($ui);
		
		$ui = new Quad();
		$ui->setPosition(400, 400);
		$ui->setStyle(null);
		$ui->setActionKey(self::F7);
		$ui->setAction($this->createAction(array($this, 'onKey'), self::F7));
		$this->addComponent($ui);
		
		$ui = new Quad();
		$ui->setPosition(400, 400);
		$ui->setStyle(null);
		$ui->setActionKey(self::F8);
		$ui->setAction($this->createAction(array($this, 'onKey'), self::F8));
		$this->addComponent($ui);
	}
	
	function addCallback($key, $callback)
	{
		if(!is_array($callback) || !is_callable($callback))
			throw new \InvalidArgumentException('Invalid callback');
		if(isset($this->onKey[$key]) && $this->onKey[$key] != null)
			throw new \Exception('This key already has a callback');
		$this->onKey[$key] = $callback;
	}
	
	// TODO this should be done automatically but PHP has no refcount function
	// nor weak references yet... so please don't forget to call this method
	// to avoid memory leaks !!!!
	function removeCallback($key)
	{
//		if($key != self::F8)
			$this->onKey[$key] = null;
	}
	
	function onKey($login, $key)
	{
		if(isset($this->onKey[$key]) && $this->onKey[$key] != null)
			call_user_func($this->onKey[$key], $login);
	}
	
	function destroy()
	{
		parent::destroy();
		
		foreach($this->onKey as $key => $callback)
		{
			$this->removeCallback($key);
		}
	}
}