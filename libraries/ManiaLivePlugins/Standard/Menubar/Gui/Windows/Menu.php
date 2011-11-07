<?php
/**
 * Menubar Plugin - Handle dynamically a menu
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\Standard\Menubar\Gui\Windows;

use ManiaLib\Gui\Layouts\Column;
use ManiaLive\Gui\Controls\Frame;
use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\Standard\Menubar\Gui\Controls\Item;

class Menu extends \ManiaLive\Gui\Window
{
	private $container;
	private $actions = array();
	private $items = array();
	private $activeItem = null;

	function onConstruct()
	{
		$this->container = new Frame(0, 0, new Column(0, 0, Column::DIRECTION_DOWN));
		$this->addComponent($this->container);
	}
	
	function addItem($name, $icon)
	{
		$item = new Item($name);
		$item->setIcon($icon);
		$hash = spl_object_hash($item);
		$action = ActionHandler::getInstance()->createAction(array($this, 'onClick'), $hash);
		$item->setAction($action);
		$this->actions[] = $action;
		$this->items[$hash] = $item;
		$this->container->addComponent($item);
		
		return $item;
	}
	
	function addFinalItem($name, $icon, $callback)
	{
		if(!is_callable($callback))
			return;
		
		$action = ActionHandler::getInstance()->createAction($callback);
		$this->actions[] = $action;
		$item = new Item($name);
		$item->setIcon($icon);
		$item->setAction($action);
		$this->container->addComponent($item);
	}
	
	function clearItems()
	{
		$this->container->clearComponents();
		foreach($this->actions as $action)
			ActionHandler::getInstance()->deleteAction($action);
		$this->actions = array();
		foreach($this->items as $item)
			$item->destroy();
		$this->items = array();
	}
	
	function onClick($login, $itemHash)
	{
		if($itemHash != $this->activeItem)
		{
			if($this->activeItem)
				$this->items[$this->activeItem]->hideSubitems();
			$this->items[$itemHash]->showSubItems();
		}
		else
			$this->items[$itemHash]->toggleSubItems();
		
		$this->activeItem = $itemHash;
	}
	
	function onDraw()
	{
		foreach($this->items as $hash => $item)
			if(!$item->hasSubitems())
			{
				$this->container->removeComponent($item);
				unset($this->items[$hash]);
			}
	}
	
	function destroy()
	{
		$this->clearItems();
		parent::destroy();
	}
}

?>