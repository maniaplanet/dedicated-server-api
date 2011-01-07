<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 1709 $:
 * @author      $Author: svn $:
 * @date        $Date: 2011-01-07 14:06:13 +0100 (ven., 07 janv. 2011) $:
 */

namespace ManiaLive\Gui\Windowing\Controls;

use ManiaLive\Gui\Toolkit\Layouts\Line;
use ManiaLive\Gui\Toolkit\Elements\BgsPlayerCard;
use ManiaLive\Gui\Toolkit\Elements\Label;
use ManiaLive\Gui\Toolkit\Elements\Bgs1;

/**
 * Tabview component like it is known from any
 * windowing system.
 * 
 * @author Florian Schnell
 */
class Tabview extends \ManiaLive\Gui\Windowing\Control
{
	protected $tabs;
	protected $background;
	protected $tab_fan;
	protected $content;
	protected $active_id;
	protected $active_id_prev;
	
	function initializeComponents()
	{
		$this->tabs = array();
		$this->active_id = 0;
		$this->active_id_prev = null;
		
		$this->background = new BgsPlayerCard();
		$this->background->setSubStyle(BgsPlayerCard::BgCardSystem);
		$this->addComponent($this->background);
		
		$this->tab_fan = new Frame();
		$this->tab_fan->applyLayout(new Line());
		$this->addComponent($this->tab_fan);
		
		$this->content = new Frame();
		$this->content->setPosition(0.5, 3.5);
		$this->addComponent($this->content);
	}
	
	function beforeDraw()
	{
		// draw content background
		$this->background->setPosition(0, 3);
		$this->background->setSize($this->getSizeX(), $this->getSizeY() - 3);
		
		$this->tab_fan->clearComponents();
		
		// build tab fan on the top
		foreach ($this->tabs as $i => $tab)
		{
			// start building fan element for tab
			$frame = new Frame(0, 0, $this->tab_fan);
			$frame->setSize(14, 3);
			{
				$ui = new Bgs1(14, 3);
				if ($i == $this->active_id)
				{
					$ui->setSubStyle(Bgs1::NavButtonBlink);
				}
				else
				{
					$ui->setSubStyle(Bgs1::NavButton);
				}
				$ui->setAction($this->callback('clickOnTab', $i));
				$frame->addComponent($ui);
				
				$ui = new Label(14, 3);
				$ui->setPosition(1, 0.4);
				$ui->setTextSize(2);
				$ui->setTextColor('fff');
				$ui->setText($tab->getTitle());
				$frame->addComponent($ui);
			}
		}
		
		// change tab content if it has been switched
		if ($this->active_id_prev !== $this->active_id)
		{
			
			// remove old tab
			if (isset($this->tabs[$this->active_id_prev]))
			{
				$old_tab = $this->tabs[$this->active_id_prev];
				$this->content->clearComponents();
				$old_tab->onDeactivate();
			}
			
			// add selected tab as content
			if (isset($this->tabs[$this->active_id]))
			{
				$new_tab = $this->tabs[$this->active_id];
				$new_tab->setSize($this->getSizeX()-1, $this->getSizeY() - 4);
				$this->content->addComponent($new_tab);
				$new_tab->onActivate();
			}
		}
		
		$this->active_id_prev = $this->active_id;
	}
	
	/**
	 * Changes the active Tab on user's click.
	 * @param string $login
	 * @param integer $id
	 */
	function clickOnTab($login, $id)
	{
		$this->active_id = $id;
		$this->getWindow()->show($login);
	}
	
	/**
	 * Adds a Tab to the Tabview.
	 * @param $tab
	 */
	function addTab(Tab $tab)
	{
		$this->tabs[] = $tab;
	}
	
	/**
	 * Returns the Tab with the specified Id.
	 * Ids are assigned to Tabs in the order they are added to the Tabview.
	 * @param $id
	 */
	function getTab($id)
	{
		if (isset($this->tabs[$id]))
		{
			return $this->tabs[$id];
		}
	}
	
	/**
	 * Returns the Id of the Tab that is currently shown.
	 * @return integer Id o currently active Tab
	 */
	function getActiveTabId()
	{
		return $this->active_id;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Gui/Windowing/ManiaLive\Gui\Windowing.Control::destroy()
	 */
	function destroy()
	{
		if ($this->tabs != null)
		{
			foreach ($this->tabs as $tab)
			{
				$tab->destroy();
			}
		}
		$this->tabs = null;
		parent::destroy();
	}
}

?>