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

namespace ManiaLive\Gui\Windowing\Controls;

use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\Icons64x64_1;
use ManiaLib\Gui\Layouts\Column;
use ManiaLib\Gui\Component;
use ManiaLib\Gui\Elements\Quad;

/**
 * Add controls to the pager, give it a size and
 * it will try to arrange them on your screen and
 * devide them onto several pages.
 *
 * @author Florian Schnell
 */
class Pager extends \ManiaLive\Gui\Windowing\Control
{
	protected $buttonNext;
	protected $buttonPrev;
	protected $currentPage;
	protected $items;
	protected $pages;
	protected $container;
	protected $label;
	protected $stretchContentX;
	
	function initializeComponents()
	{
		$this->items = array();
		$this->currentPage = 0;
		$this->stretchContentX = false;
		
		$this->buttonNext = new Quad(5, 5);
		$this->buttonNext->setStyle(Icons64x64_1::Icons64x64_1);
		$this->buttonNext->setSubStyle(Icons64x64_1::ArrowNext);
		$this->addComponent($this->buttonNext);
		
		$this->buttonPrev = new Quad(5, 5);
		$this->buttonPrev->setStyle(Icons64x64_1::Icons64x64_1);
		$this->buttonPrev->setSubStyle(Icons64x64_1::ArrowPrev);
		$this->addComponent($this->buttonPrev);
		
		$this->container = new Frame(2, 0, new Column());
		$this->addComponent($this->container);
		
		$this->label = new Label();
		$this->label->setHalign('center');
		$this->addComponent($this->label);
	}
	
	function onResize()
	{
		$current = false;
		if (isset($this->pages[$this->currentPage])
			&& isset($this->pages[$this->currentPage][0]))
		{
			$current = $this->pages[$this->currentPage][0];
		}
		
		$this->pages = array(array());
		$currentSizeY = 0;
		$pageCount = 0;
		
		foreach ($this->items as $item)
		{
			$currentSizeY += $item->getSizeY();
			if ($currentSizeY > $this->sizeY - 5)
			{
				$currentSizeY = $item->getSizeY();
				$this->pages[] = array($item);
				$pageCount = count($this->pages) - 1;
			}
			else
			{
				$this->pages[$pageCount][] = $item;
			}
			if ($current === $item)
			{
				$this->currentPage = $pageCount;
			}
		}
		$this->label->setPosition($this->sizeX / 2, $this->sizeY - 3.5);
	}
	
	function getPages()
	{
		return count($this->pages);
	}
	
	function beforeDraw()
	{
		// refresh container components.
		$this->container->clearComponents();
		
		if (isset($this->pages[$this->currentPage]))
		{
			foreach ($this->pages[$this->currentPage] as $item)
			{
				if ($this->stretchContentX) $item->setSizeX($this->sizeX - 4);
				$this->container->addComponent($item);
			}
		}
		
		// page label
		$this->label->setText(($this->currentPage + 1) . ' / ' . count($this->pages));
		
		// draw forward and back buttons
		if ($this->currentPage <= 0)
		{
			$this->buttonPrev->setAction(null);
			$this->buttonPrev->setSubStyle(Icons64x64_1::StarGold);
		}
		else 
		{
			$this->buttonPrev->setSubStyle(Icons64x64_1::ArrowPrev);
			$this->buttonPrev->setAction($this->callback('buttonPrev'));
		}
		$this->buttonPrev->setPosition(0, $this->sizeY - 5);
		
		if ($this->currentPage >= count($this->pages) - 1)
		{
			$this->buttonNext->setAction(null);
			$this->buttonNext->setSubStyle(Icons64x64_1::StarGold);
		}
		else
		{
			$this->buttonNext->setAction($this->callback('buttonNext'));
			$this->buttonNext->setSubStyle(Icons64x64_1::ArrowNext);
		}
		$this->buttonNext->setPosition($this->sizeX - 5, $this->sizeY - 5);
	}
	
	function afterDraw() {}
	
	function setStretchContentX($stretch)
	{
		$this->stretchContentX = $stretch;
	}
	
	function buttonNext($login)
	{
		$this->currentPage++;
		$this->redraw();
	}
	
	function buttonPrev($login)
	{
		$this->currentPage--;
		$this->redraw();
	}
	
	function clearItems()
	{
		$this->container->clearComponents();
		$temp = $this->items;
		$this->items = array();
		$this->pages = array(array());
		return $temp;
	}
	
	function addItem(Component $item)
	{
		$this->items[] = $item;
	}
	
	function destroy()
	{
		$this->items = null;
		$this->pages = null;
		
		parent::destroy();
	}
}

?>