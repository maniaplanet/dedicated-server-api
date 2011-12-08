<?php
/**
 * Profiler Plugin - Show statistics about ManiaLive
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\Standard\Profiler\Gui\Controls;

use ManiaLib\Gui\Elements\Button;
use ManiaLive\Event\Dispatcher;
use ManiaLive\PluginHandler\Listener as PluginListener;
use ManiaLive\PluginHandler\Event as PluginEvent;
use ManiaLive\Gui\ActionHandler;
use ManiaLive\Gui\Controls\Pager;
use ManiaLive\PluginHandler\PluginHandler;

use ManiaLivePlugins\Standard\Profiler\Profiler;

class PluginsTab extends \ManiaLive\Gui\Controls\Tabbable implements PluginListener
{
	private $managerButton;
	private $pluginsPager;
	private $pluginManagerAvailable = false;
	private $pluginItems;
	
	function __construct()
	{
		$this->setTitle('Plugins');
		
		$this->pluginItems = array();
		
		$this->managerButton = new Button();
		$this->managerButton->setHalign('center');
		$this->managerButton->setStyle(Button::CardButtonSmallWide);
		$this->managerButton->setText('click here to manage your plugins');
		$this->managerButton->setAction(ActionHandler::getInstance()->createAction(array($this, 'showPluginManager')));
		$this->addComponent($this->managerButton);
		
		$this->pluginsPager = new Pager();
		$this->pluginsPager->setStretchContentX(true);
		$this->addComponent($this->pluginsPager);
		
		foreach (PluginHandler::getInstance()->getLoadedPluginsList() as $plugin)
			$this->onPluginLoaded($plugin);
		
		Dispatcher::register(PluginEvent::getClass(), $this);
	}
	
	function showPluginManager($login)
	{
		PluginHandler::getInstance()->callPublicMethod(Profiler::$me, 'Standard\PluginManager', 'openWindow', array($login));
	}
	
	function onDraw()
	{
		$this->managerButton->setVisibility($this->pluginManagerAvailable);
		$this->pluginsPager->setPosition(0, $this->pluginManagerAvailable ? -8 : -2);
	}
	
	protected function onResize($oldX, $oldY)
	{
		$this->managerButton->setPosition($this->sizeX / 2, -1);
		$this->pluginsPager->setSize($this->sizeX, $this->sizeY - ($this->pluginManagerAvailable ? 12 : 2));
	}
	
	function onPluginLoaded($pluginId)
	{
		$plugin = explode('\\', $pluginId);
		$author = array_shift($plugin);
		$package = implode('\\', $plugin);

		$item = new ListItem();
		$item->label->setText('Author: '.$author.' - Package: '.$package);
		$this->pluginsPager->addItem($item);
		$this->pluginItems[$pluginId] = $item;
		
		if($pluginId == 'Standard\PluginManager')
			$this->pluginManagerAvailable = true;
		
		$this->redraw();
	}
	
	function onPluginUnloaded($pluginId)
	{
		$this->pluginsPager->removeItem($this->pluginItems[$pluginId]);
		unset($this->pluginItems[$pluginId]);
		
		if($pluginId == 'Standard\PluginManager')
			$this->pluginManagerAvailable = false;
		
		$this->redraw();
	}
	
	function destroy()
	{
		Dispatcher::unregister(PluginEvent::getClass(), $this);
		parent::destroy();
	}
}

?>