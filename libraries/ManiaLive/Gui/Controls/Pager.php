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

use ManiaLib\Gui\Component;
use ManiaLib\Gui\Elements\Icons64x64_1;
use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Layouts\Column;

/**
 * Add controls to the pager, give it a size and
 * it will try to arrange them on your screen and
 * devide them onto several pages.
 *
 * @author Florian Schnell
 */
class Pager extends \ManiaLive\Gui\Control
{
	private $buttonNext;
	private $buttonPrev;
	private $container;
	private $label;

	private $actionNext;
	private $actionPrev;
	
	private $items;
	private $pages;
	private $currentPage;
	
	private $stretchContentX;
	private $needRefresh;
	
	function __construct()
	{
		$this->items = array();
		$this->currentPage = 0;
		$this->stretchContentX = false;
		$this->needRefresh = false;

		$this->actionNext = $this->createAction(array($this, 'nextPage'));
		$this->actionPrev = $this->createAction(array($this, 'previousPage'));

		$this->buttonNext = new Icons64x64_1(9);
		$this->buttonNext->setSubStyle(Icons64x64_1::ArrowNext);
		$this->addComponent($this->buttonNext);

		$this->buttonPrev = new Icons64x64_1(9);
		$this->buttonPrev->setSubStyle(Icons64x64_1::ArrowPrev);
		$this->addComponent($this->buttonPrev);

		$this->container = new Frame(0, 0, new Column());
		$this->addComponent($this->container);

		$this->label = new Label();
		$this->label->setHalign('center');
		$this->addComponent($this->label);
	}

	function addItem(Component $item)
	{
		$this->items[spl_object_hash($item)] = $item;
		$this->needRefresh = true;
	}

	function removeItem(Component $item)
	{
		unset($this->items[spl_object_hash($item)]);
		$this->needRefresh = true;
	}

	function clearItems()
	{
		$this->items = array();
		$this->needRefresh = true;
	}

	function orderfromTopToBottom()
	{
		$this->container->getLayout()->setDirection($this->direction);
		$this->container->setPosY(0);
		$this->needRefresh = true;
	}

	function orderFromBottomToTop()
	{
		$this->container->getLayout()->setDirection($this->direction);
		$this->container->setPosY(5.3 - $this->sizeY);
		$this->needRefresh = true;
	}

	function setStretchContentX($stretch)
	{
		$this->stretchContentX = $stretch;
	}

	function getPages()
	{
		return count($this->pages);
	}

	function nextPage($login)
	{
		$this->currentPage++;
		$this->redraw();
	}

	function previousPage($login)
	{
		$this->currentPage--;
		$this->redraw();
	}

	protected function onResize($oldX, $oldY)
	{
		$this->buttonPrev->setPosition(0, 5 - $this->sizeY);
		$this->buttonNext->setPosition($this->sizeX - 10, 5 - $this->sizeY);
		$this->label->setPosition($this->sizeX / 2, 1.5 - $this->sizeY);
		if($this->container->getPosY())
			$this->container->setPosY(5.3 - $this->sizeY);
	}

	function onDraw()
	{
		if($this->needRefresh)
			$this->refreshPages();

		// refresh container components.
		$this->container->clearComponents();

		if(isset($this->pages[$this->currentPage]))
		{
			foreach($this->pages[$this->currentPage] as $item)
			{
				if($this->stretchContentX)
					$item->setSizeX($this->sizeX - 4);
				$this->container->addComponent($item);
			}
		}

		// page label
		if(count($this->pages) > 1)
		{
			$this->label->setText(($this->currentPage + 1).' / '.count($this->pages));
		
			// draw forward and back buttons
			if($this->currentPage <= 0)
			{
				$this->buttonPrev->setAction(null);
				$this->buttonPrev->setSubStyle(Icons64x64_1::ClipPause);
			}
			else
			{
				$this->buttonPrev->setSubStyle(Icons64x64_1::ArrowPrev);
				$this->buttonPrev->setAction($this->actionPrev);
			}

			if($this->currentPage >= count($this->pages) - 1)
			{
				$this->buttonNext->setAction(null);
				$this->buttonNext->setSubStyle(Icons64x64_1::ClipPause);
			}
			else
			{
				$this->buttonNext->setAction($this->actionNext);
				$this->buttonNext->setSubStyle(Icons64x64_1::ArrowNext);
			}
		}
		else
		{
			$this->buttonPrev->setAction(null);
			$this->buttonPrev->setSubStyle(Icons64x64_1::EmptyIcon);
			$this->buttonNext->setAction(null);
			$this->buttonNext->setSubStyle(Icons64x64_1::EmptyIcon);
		}
	}

	private function refreshPages()
	{
		$current = null;
		if(isset($this->pages[$this->currentPage]) && isset($this->pages[$this->currentPage][0]))
				$current = $this->pages[$this->currentPage][0];

		$this->pages = array();
		$currentSizeY = 0;
		$maxSizeY = $this->sizeY - 5.3;
		$pageCount = 0;

		foreach($this->items as $item)
		{
			$currentSizeY += $item->getSizeY();
			if($currentSizeY > $maxSizeY)
			{
				$currentSizeY = $item->getSizeY();
				++$pageCount;
			}
			$this->pages[$pageCount][] = $item;

			if($current === $item)
				$this->currentPage = $pageCount;
		}
	}

	function destroy()
	{
		$this->items = null;
		$this->pages = null;

		parent::destroy();
	}
}

?>