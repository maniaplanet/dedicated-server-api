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

namespace ManiaLive\Gui\Controls;

use ManiaLib\Gui\Layouts\Line;
use ManiaLib\Gui\Elements\Bgs1;
use ManiaLib\Gui\Elements\BgsPlayerCard;
use ManiaLib\Gui\Elements\Label;

/**
 * Tabview component like it is known from any
 * windowing system.
 * 
 * @author Florian Schnell
 */
class TabbedPane extends \ManiaLive\Gui\Control
{
	protected $panes;
	protected $tabs;
	protected $activeId;
	
	protected $background;
	protected $tabsView;
	protected $content;
	
	function __construct()
	{
		$this->panes = array();
		$this->tabs = array();
		$this->activeId = -1;
		
		$this->background = new BgsPlayerCard();
		$this->background->setSubStyle(BgsPlayerCard::BgCardSystem);
		$this->background->setPosition(0, -5);
		$this->background->setSize($this->sizeX, $this->sizeY - 5);
		$this->addComponent($this->background);
		
		$this->tabsView = new Frame(0, 0, new Line());
		$this->addComponent($this->tabsView);
		
		$this->content = new Frame();
		$this->content->setPosition(0.5, -5);
		$this->addComponent($this->content);
	}
	
	protected function onResize($oldX, $oldY)
	{
		$this->panes[$this->activeId]->setSize($this->sizeX - 1, $this->sizeY - 5.5);
		$this->background->setSize($this->sizeX, $this->sizeY - 5);
	}
	
	/**
	 * Changes the active Tab on user's click.
	 * @param string $login
	 * @param integer $id
	 */
	function clickOnTab($login, $id)
	{
		if($id !== $this->activeId)
		{
			if(isset($this->panes[$this->activeId]))
			{
				$this->panes[$this->activeId]->onDeactivate();
				if(isset($this->tabs[$this->activeId]))
					$this->tabs[$this->activeId]->background->setSubStyle(Bgs1::NavButton);
			}
			
			$this->activeId = $id;
			if(isset($this->panes[$this->activeId]))
			{
				$this->panes[$this->activeId]->onActivate();
				$this->panes[$this->activeId]->setSize($this->sizeX - 1, $this->sizeY - 5.5);
				$this->content->clearComponents();
				$this->content->addComponent($this->panes[$this->activeId]);
				if(isset($this->tabs[$this->activeId]))
					$this->tabs[$this->activeId]->background->setSubStyle(Bgs1::NavButtonBlink);
			}
			
			$this->redraw();
		}
	}
	
	/**
	 * Adds a Tab to the Tabview.
	 * @param Tabbable $pane
	 */
	function addTab(Tabbable $pane)
	{
		$this->panes[] = $pane;
		
		$tab = new Tab();
		$tab->setSize(25, 5);
		$index = count($this->tabs);
		$tab->background->setAction($this->createAction(array($this, 'clickOnTab'), $index));
		$tab->label->setText($pane->getTitle());
		
		$this->tabs[] = $tab;
		$this->tabsView->addComponent($tab);
		
		if($this->activeId == -1)
			$this->clickOnTab(null, 0);
	}
	
	/**
	 * Returns the Tab with the specified Id.
	 * Ids are assigned to Tabs in the order they are added to the Tabview.
	 * @param integer $id
	 * @return Tabbable
	 */
	function getTab($id)
	{
		if (isset($this->panes[$id]))
			return $this->panes[$id];
	}
	
	/**
	 * Returns the Id of the Tab that is currently shown.
	 * @return integer Id o currently active Tab
	 */
	function getActiveTabId()
	{
		return $this->activeId;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see libraries/ManiaLive/Gui/Windowing/ManiaLive\Gui\Windowing.Control::destroy()
	 */
	function destroy()
	{
		$this->tabsView->destroy();
		$this->content->clearComponents();
		
		$this->content = null;
		$this->tabs = array();
		parent::destroy();
	}
}

class Tab extends Frame
{
	public $background;
	public $label;
	
	function __construct($sizeX=25, $sizeY=5)
	{
		$this->background = new Bgs1($sizeX, $sizeY);
		$this->background->setSubStyle(Bgs1::NavButton);
		$this->addComponent($this->background);

		$this->label = new Label($sizeX - 2, $sizeY);
		$this->label->setPosition(1, -0.4);
		$this->label->setTextSize(2);
		$this->label->setTextColor('fff');
		$this->addComponent($this->label);
	}
}

?>