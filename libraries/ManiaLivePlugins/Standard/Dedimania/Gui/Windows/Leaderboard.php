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
namespace ManiaLivePlugins\Standard\Dedimania\Gui\Windows;

use ManiaLive\Gui\Windowing\WindowHandler;

use ManiaLivePlugins\Standard\Menubar\Gui\Windows\Menu;

use ManiaLive\PluginHandler\PluginHandler;

use ManiaLib\Gui\Elements\Bgs1InRace;
use ManiaLib\Gui\Tools;
use ManiaLib\Gui\Elements\Icons64x64_1;
use ManiaLib\Gui\Elements\BgsPlayerCard;
use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\Entry;
use ManiaLib\Gui\Elements\Quad;
use ManiaLib\Gui\Elements\Button;
use ManiaLib\Gui\Layouts\Flow;
use ManiaLivePlugins\Standard\Dedimania\Gui\Controls\LeaderboardHeaderCell;
use ManiaLivePlugins\Standard\Dedimania\Gui\Controls\LeaderboardCell;
use ManiaLive\Data\Storage;
use ManiaLive\Gui\Windowing\Controls\ButtonResizeable;
use ManiaLive\Gui\Windowing\Windows\Info;
use ManiaLive\Gui\Windowing\Controls\PageNavigator;
use ManiaLive\Gui\Windowing\Controls\Panel;
use ManiaLive\Gui\Windowing\Controls\Frame;
use ManiaLive\Utilities\Time;
use ManiaLive\Utilities\Console;

class Leaderboard extends \ManiaLive\Gui\Windowing\ManagedWindow
{
	//components ...
	private $navigator;
	private $table;
	private $btn_player;
	private $btn_website;
	private $navigator_back;

	private $page;
	private $records;
	private $page_last;
	private $page_items;
	private $item_height;
	private $table_height;
	private $columns;
	private $info;
	private $highlight;

	function initializeComponents()
	{
		$this->page = 1;
		$this->page_last = 1;
		$this->item_height = 4;
		$this->table_height = 0;
		$this->records = array();
		$this->columns = array();
		$this->highlight = false;

		$this->setTitle('Dedimania Rankings');
		$this->setMaximizable();

		// add background for navigation elements ...
		$this->navigator_back = new BgsPlayerCard();
		$this->navigator_back->setSubStyle(BgsPlayerCard::BgCardSystem);
		$this->addComponent($this->navigator_back);

		// create records-table ...
		$this->table = new Frame($this->getSizeX() - 4, $this->getSizeY() - 18);
		$this->table->applyLayout(new Flow());
		$this->table->setPosition(2, 6);
		$this->addComponent($this->table);

		// add show player button ..
		$this->btn_player = new ButtonResizeable(18, 4);
		$this->btn_player->setText('Personal Record');
		$this->btn_player->setAction($this->callback('showPersonalRecord'));
		$this->addComponent($this->btn_player);

		// add show player button ..
		$this->btn_website = new ButtonResizeable(18, 4);
		$this->btn_website->setText('More Records');
		$this->addComponent($this->btn_website);

		// create page navigator ...
		$this->navigator = new PageNavigator();
		$this->addComponent($this->navigator);
	}

	function onResize()
	{
		$this->table->setSize($this->getSizeX() - 4, $this->getSizeY() - 21);
		$this->calculatePages();
	}

	function onDraw()
	{
		if (PluginHandler::getInstance()->isPluginLoaded('Standard\Menubar'))
		{
			// move on the z-axis, in front of the menu
			$menu = Menu::Create($this->getRecipient());
			$this->moveAbove($menu);
		}

		// refresh table ...
		$this->table->clearComponents();

		// create table header ...
		foreach ($this->columns as $name => $percent)
		{
			$cell = new LeaderboardHeaderCell($percent * $this->table->getSizeX(), $this->item_height + 1);
			$cell->setText($name);

			$this->table->addComponent($cell);
		}

		// create table body ...
		$count = count($this->records);
		$max = $this->page_items * $this->page;
		for ($i = $this->page_items * ($this->page - 1); $i < $count && $i < $max; $i++)
		{
			$record = $this->records[$i];

			foreach ($this->columns as $name => $percent)
			{
				$cell = new LeaderboardCell($percent * $this->table->getSizeX(), $this->item_height);

				if ($this->highlight && $record['Login'] == $this->getRecipient())
				{
					$cell->setHighlight(true);
				}

				if (isset($record[$name]))
					$cell->setText($record[$name]);
				else
					$cell->setText('n/a');

				$this->table->addComponent($cell);
			}
		}

		// add page navigator to the bottom ...
		$this->navigator->setPositionX($this->getSizeX() / 2);
		$this->navigator->setPositionY($this->getSizeY() - 4);

		// place personal record button ...
		$this->btn_player->setPosition(3, $this->getSizeY() - 6.2);

		// place website button ...
		$this->btn_website->setHalign('right');
		$this->btn_website->setPosition($this->getSizeX() - 3, $this->getSizeY() - 6.2);
		$this->btn_website->setUrl('http://dedimania.net/tmstats/?do=stat&Uid=' . Storage::getInstance()->currentChallenge->uId . '&Show=RECORDS');

		// place navigation background ...
		$this->navigator_back->setValign('bottom');
		$this->navigator_back->setSize($this->getSizeX() - 0.6, 8);
		$this->navigator_back->setPosition(0.3, $this->getSizeY() - 0.3);

		// configure ...
		$this->navigator->setCurrentPage($this->page);
		$this->navigator->setPageNumber($this->page_last);
		$this->navigator->showText(true);
		$this->navigator->showLast(true);

		if ($this->page < $this->page_last && $this->info == null)
		{
			$this->navigator->arrowNext->setAction($this->callback('showNextPage'));
			$this->navigator->arrowLast->setAction($this->callback('showLastPage'));
		}
		else
		{
			$this->navigator->arrowNext->setAction(null);
			$this->navigator->arrowLast->setAction(null);
		}

		if ($this->page > 1 && $this->info == null)
		{
			$this->navigator->arrowPrev->setAction($this->callback('showPrevPage'));
			$this->navigator->arrowFirst->setAction($this->callback('showFirstPage'));
		}
		else
		{
			$this->navigator->arrowPrev->setAction(null);
			$this->navigator->arrowFirst->setAction(null);
		}
	}

	function onHide()
	{
		$this->showFirstPage();
		$this->highlight = false;
	}

	function showPersonalRecord()
	{
		$found = false;
		foreach ($this->records as $i => $record)
		{
			if ($record['Login'] == $this->getRecipient())
			{
				$found = $i;
			}
		}

		if ($found === false)
		{
			$info = Info::Create($this->getRecipient(), false);
			$info->setSize(40, 13);
			$info->setTitle('No Record');
			$info->setText('You don\'t have a record on this challenge yet!');

			$info->centerOn($this);
			WindowHandler::showDialog($info);
		}
		else
		{
			$this->highlight = true;
			$this->page = floor($found / $this->page_items) + 1;

			$this->show();
		}
	}

	function calculatePages()
	{
		$this->page_items = floor($this->table->getSizeY() / $this->item_height);
		$this->page_last = ceil(count($this->records) * $this->item_height / max(1, $this->table->getSizeY()));
		if ($this->page > $this->page_last)
		{
			$this->page = max(array(1, $this->page_last));
		}
	}

	function addColumn($name, $percent)
	{
		$this->columns[$name] = $percent;
	}

	function clearRecords()
	{
		$this->records = array();
	}

	function addRecord($record)
	{
		if (is_array($record))
		{
			$this->records[] = $record;
			$this->calculatePages();
		}
	}

	function showPrevPage($login = null)
	{
		$this->page--;
		if ($login) $this->show();
	}

	function showNextPage($login = null)
	{
		$this->page++;
		if ($login) $this->show();
	}

	function showLastPage($login = null)
	{
		$this->page = $this->page_last;
		if ($login) $this->show();
	}

	function showFirstPage($login = null)
	{
		$this->page = 1;
		if ($login) $this->show();
	}

	function destroy()
	{
		parent::destroy();
	}
}

?>