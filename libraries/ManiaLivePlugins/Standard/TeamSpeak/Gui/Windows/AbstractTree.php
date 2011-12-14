<?php
/**
 * TeamSpeak Plugin - Connect to a TeamSpeak 3 server
 * Original work by refreshfr
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\Standard\TeamSpeak\Gui\Windows;

use ManiaLib\Gui\Elements\Quad;
use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Layouts\Column;
use ManiaLive\Gui\Controls\Frame;
use ManiaLivePlugins\Standard\TeamSpeak\Gui\Images;

/**
 * Description of AbstractTree
 */
abstract class AbstractTree extends \ManiaLive\Gui\Window
{
	const INDEX_ELEMENT = 0;
	const INDEX_PARENTS = 1;
	
	private $elementsByParent = array(-1 => array());
	private $parentIds = array();
	
	private $flatteningNeeded = true;
	private $flattenElements = array();
	private $offset = 0;
	private $firstOffset = 0;
	private $lastOffset = 0;
	private $nbElementsToShow = 20;
	
	private $arrowFirstAction;
	private $arrowFastUpAction;
	private $arrowUpAction;
	private $arrowDownAction;
	private $arrowFastDownAction;
	private $arrowLastAction;
	
	private $arrowFirst;
	private $arrowFastUp;
	private $arrowUp;
	private $arrowDown;
	private $arrowFastDown;
	private $arrowLast;
	private $arrowsBg;
	private $arrowsUpFrame;
	private $arrowsDownFrame;
	private $elementsFrame;
	
	private $emptyBg;
	private $emptyLabel;
	
	protected function onConstruct()
	{
		$this->arrowFirstAction    = $this->createAction(array($this, 'upToFirst'));
		$this->arrowFastUpAction   = $this->createAction(array($this, 'upFast'));
		$this->arrowUpAction       = $this->createAction(array($this, 'up'));
		$this->arrowDownAction     = $this->createAction(array($this, 'down'));
		$this->arrowFastDownAction = $this->createAction(array($this, 'downFast'));
		$this->arrowLastAction     = $this->createAction(array($this, 'downToLast'));
		
		$this->arrowFirst      = new Quad(5, 5);
		$this->arrowFastUp     = new Quad(5, 5);
		$this->arrowUp         = new Quad(5, 5);
		$this->arrowDown       = new Quad(5, 5);
		$this->arrowFastDown   = new Quad(5, 5);
		$this->arrowLast       = new Quad(5, 5);
		$this->arrowsBg        = new Quad(5, 30);
		$this->arrowsUpFrame   = new Frame();
		$this->arrowsDownFrame = new Frame();
		$this->elementsFrame   = new Frame();
		
		$this->arrowsBg->setBgcolor('0008');
		$layout = new Column();
		$layout->setMarginHeight(.2);
		$this->elementsFrame->setLayout(clone $layout);
		$this->arrowsUpFrame->setLayout(clone $layout);
		$layout->setDirection(Column::DIRECTION_UP);
		$this->arrowsDownFrame->setLayout($layout);
		
		$this->arrowsUpFrame->addComponent($this->arrowFirst);
		$this->arrowsUpFrame->addComponent($this->arrowFastUp);
		$this->arrowsUpFrame->addComponent($this->arrowUp);
		$this->arrowsDownFrame->addComponent($this->arrowLast);
		$this->arrowsDownFrame->addComponent($this->arrowFastDown);
		$this->arrowsDownFrame->addComponent($this->arrowDown);
		$this->addComponent($this->elementsFrame);
		$this->addComponent($this->arrowsBg);
		$this->addComponent($this->arrowsUpFrame);
		$this->addComponent($this->arrowsDownFrame);
		
		$this->emptyBg = new Quad(30, 5);
		$this->emptyBg->setVisibility(false);
		$this->addComponent($this->emptyBg);
		$this->emptyLabel = new Label(28, 5);
		$this->emptyLabel->setTextSize(1);
		$this->emptyLabel->setTextColor('bbbb');
		$this->emptyLabel->setAlign('center', 'center2');
		$this->emptyLabel->setPosition(15, -2.5);
		$this->emptyLabel->setVisibility(false);
		$this->emptyLabel->setText('Empty');
		$this->addComponent($this->emptyLabel);
	}
	
	function addElement(\ManiaLive\Gui\Control $element, $parent=null)
	{
		$elementId = spl_object_hash($element);
		if(isset($this->parentIds[$elementId]))
			$this->moveElement($element, $parent);
		else
		{
			$parentId = $parent ? spl_object_hash($parent) : -1;
			if(!isset($this->elementsByParent[$parentId]))
				$this->elementsByParent[$parentId] = array();
			$this->elementsByParent[$parentId][$elementId] = $element;
			$this->parentIds[$elementId] = $parentId;
			$this->flatteningNeeded = true;
		}
	}
	
	function moveElement(\ManiaLive\Gui\Control $element, $newParent=null)
	{
		$elementId = spl_object_hash($element);
		if(!isset($this->parentIds[$elementId]))
			$this->addElement($element, $newParent);
		else
		{
			$oldParentId = $this->parentIds[$elementId];
			$newParentId = $newParent ? spl_object_hash($newParent) : -1;
			if($oldParentId != $newParentId)
			{
				unset($this->elementsByParent[$oldParentId][$elementId]);
				$this->elementsByParent[$newParentId][$elementId] = $element;
				$this->parentIds[$elementId] = $newParentId;
			}
			$this->flatteningNeeded = true;
		}
	}
	
	function removeElement(\ManiaLive\Gui\Control $element, $includeChildren=true)
	{
		$elementId = spl_object_hash($element);
		if(!isset($this->parentIds[$elementId]))
			return;
		
		$parentId = $this->parentIds[$elementId];
		
		unset($this->elementsByParent[$parentId][$elementId]);
		unset($this->parentIds[$elementId]);
		if(!$includeChildren)
			$this->elementsByParent[$parentId] = array_merge($this->elementsByParent[$parentId], $this->elementsByParent[$elementId]);
		unset($this->elementsByParent[$elementId]);
		$this->flatteningNeeded = true;
	}
	
	function clearElements()
	{
		$this->elementsByParent = array(-1 => array());
		$this->parentsIds = array();
		$this->offset = 0;
		$this->flatteningNeeded = true;
	}
	
	function setNbElementsToShow($n)
	{
		if($this->nbElementsToShow != $n)
		{
			$this->nbElementsToShow = $n;
			$this->redraw();
		}
	}
	
	function setOffset($offset)
	{
		if($this->offset != $offset)
		{
			$this->offset = $offset;
			$this->redraw();
		}
	}
	
	function upToFirst($login=null)
	{
		$this->setOffset($this->firstOffset);
	}
	
	function upFast($login=null)
	{
		$this->setOffset(max($this->offset - 4, $this->firstOffset));
		$this->up($login);
	}
	
	function up($login=null)
	{
		$step = 0;
		$parents = $this->flattenElements[$this->offset][self::INDEX_PARENTS];
		while($this->offset - (++$step) > $this->firstOffset && $step - 1 < count($parents))
			if($parents[$step - 1] != $this->offset - $step)
				break;
		$this->setOffset(max($this->offset - $step, $this->firstOffset));
	}
	
	function down($login=null)
	{
		$step = 0;
		while($this->offset + (++$step) < $this->lastOffset)
		{
			$element = $this->flattenElements[$this->offset + $step][self::INDEX_ELEMENT];
			if(empty($this->elementsByParent[spl_object_hash($element)]))
				break;
		}
		$this->setOffset(min($this->offset + $step, $this->lastOffset));
	}
	
	function downFast($login=null)
	{
		$this->setOffset(min($this->offset + 4, $this->lastOffset));
		$this->down($login);
	}
	
	function downToLast($login=null)
	{
		$this->setOffset($this->lastOffset);
	}
	
	protected function onResize($oldX, $oldY)
	{
		$this->arrowsBg->setSizeY(max($this->sizeY, 31.2));
		$this->arrowsBg->setPosX($this->sizeX + .2);
		$this->arrowsUpFrame->setPosX($this->sizeX + .2);
		$this->arrowsDownFrame->setPosition($this->sizeX + .2, -max($this->sizeY, 31.2));
	}
	
	function setBarBgcolor($bgcolor)
	{
		$this->arrowsBg->setBgcolor($bgcolor);
	}
	
	function onDraw()
	{
		if($this->flatteningNeeded)
		{
			$this->flattenElements = $this->flatten();
			if(count($this->flattenElements) > $this->nbElementsToShow)
			{
				$this->lastOffset = count($this->flattenElements) - $this->nbElementsToShow;
				$maxCount = 0;
				while(count($this->flattenElements[$this->lastOffset][self::INDEX_PARENTS]) > $maxCount++)
					++$this->lastOffset;
				$this->arrowsBg->setVisibility(true);
				$this->arrowsUpFrame->setVisibility(true);
				$this->arrowsDownFrame->setVisibility(true);
			}
			else
			{
				$this->lastOffset = 0;
				$this->arrowsBg->setVisibility(false);
				$this->arrowsUpFrame->setVisibility(false);
				$this->arrowsDownFrame->setVisibility(false);
				if(count($this->flattenElements) == 0)
				{
					$this->emptyBg->setBgcolor($this->arrowsBg->getBgcolor());
					$this->emptyBg->setVisibility(true);
					$this->emptyLabel->setVisibility(true);
				}
				else
				{
					$this->emptyBg->setVisibility(false);
					$this->emptyLabel->setVisibility(false);
				}
			}
			$this->firstOffset = 0;
			while($this->firstOffset + 1 < count($this->flattenElements)
					&& count($this->flattenElements[$this->firstOffset + 1][self::INDEX_PARENTS]) == $this->firstOffset + 1)
				++$this->firstOffset;
			
			if($this->offset < $this->firstOffset)
				$this->offset = $this->firstOffset;
			else if($this->offset > $this->lastOffset)
				$this->offset = $this->lastOffset;
		}
		
		if(!count($this->flattenElements))
			return;
		// Compute array of elements to show
		$elementsToShow = array_slice($this->flattenElements, $this->offset);
		$first = reset($elementsToShow);
		foreach($first[self::INDEX_PARENTS] as $parentIndex)
			array_unshift($elementsToShow, $this->flattenElements[$parentIndex]);
		
		$images = Images::getInstance();
		// Set actions and styles for up arrows
		if($this->offset > $this->firstOffset)
		{
			$this->arrowUp->setAction($this->arrowUpAction);
			$this->arrowUp->setImage($images->arrowUp, true);
			$this->arrowUp->setImageFocus($images->arrowUpFocus, true);
			$this->arrowFastUp->setAction($this->arrowFastUpAction);
			$this->arrowFastUp->setImage($images->arrowFastUp, true);
			$this->arrowFastUp->setImageFocus($images->arrowFastUpFocus, true);
			$this->arrowFirst->setAction($this->arrowFirstAction);
			$this->arrowFirst->setImage($images->arrowFirstUp, true);
			$this->arrowFirst->setImageFocus($images->arrowFirstUpFocus, true);
		}
		else
		{
			$this->arrowUp->setAction(null);
			$this->arrowUp->setImage($images->noArrow, true);
			$this->arrowUp->setImageFocus(null);
			$this->arrowFastUp->setAction(null);
			$this->arrowFastUp->setImage($images->noArrow, true);
			$this->arrowFastUp->setImageFocus(null);
			$this->arrowFirst->setAction(null);
			$this->arrowFirst->setImage($images->noArrow, true);
			$this->arrowFirst->setImageFocus(null);
		}
		// Set actions and styles for down arrows
		if($this->offset < $this->lastOffset)
		{
			$this->arrowDown->setAction($this->arrowDownAction);
			$this->arrowDown->setImage($images->arrowDown, true);
			$this->arrowDown->setImageFocus($images->arrowDownFocus, true);
			$this->arrowFastDown->setAction($this->arrowFastDownAction);
			$this->arrowFastDown->setImage($images->arrowFastDown, true);
			$this->arrowFastDown->setImageFocus($images->arrowFastDownFocus, true);
			$this->arrowLast->setAction($this->arrowLastAction);
			$this->arrowLast->setImage($images->arrowLastDown, true);
			$this->arrowLast->setImageFocus($images->arrowLastDownFocus, true);
			$elementsToShow = array_slice($elementsToShow, 0, $this->nbElementsToShow);
		}
		else
		{
			$this->arrowDown->setAction(null);
			$this->arrowDown->setImage($images->noArrow, true);
			$this->arrowDown->setImageFocus(null);
			$this->arrowFastDown->setAction(null);
			$this->arrowFastDown->setImage($images->noArrow, true);
			$this->arrowFastDown->setImageFocus(null);
			$this->arrowLast->setAction(null);
			$this->arrowLast->setImage($images->noArrow, true);
			$this->arrowLast->setImageFocus(null);
		}
		
		// Add elements to the frame
		$this->elementsFrame->clearComponents();
		$sizeY = 0;
		foreach($elementsToShow as $elementAndParents)
		{
			$element = $elementAndParents[self::INDEX_ELEMENT];
			$posX = $element->getSizeY() * count($elementAndParents[self::INDEX_PARENTS]) / 2;
			$element->setPosition($posX, 0);
			$element->setSizeX($this->sizeX - $posX);
			$this->elementsFrame->addComponent($element);
			$this->onShowElement($element, count($elementAndParents[self::INDEX_PARENTS]));
			$sizeY += $element->getSizeY() + .2;
		}
		$this->setSizeY($sizeY - .2);
	}
	
	protected function onShowElement($element, $nbParents) {}
	
	private function flatten($parent=-1, $parentIndexes=array())
	{
		$flattenArray = array();
		$parentIndex = count($parentIndexes) ? reset($parentIndexes) : -1;
		foreach($this->elementsByParent[$parent] as $elementId => $element)
		{
			array_push($flattenArray, array($element, $parentIndexes));
			if(isset($this->elementsByParent[$elementId]))
			{
				array_unshift($parentIndexes, $parentIndex + count($flattenArray));
				array_splice($flattenArray, count($flattenArray), 0, $this->flatten($elementId, $parentIndexes));
				array_shift($parentIndexes);
			}
		}
		return $flattenArray;
	}
	
	function destroy()
	{
		parent::destroy();
		$this->clearElements();
		$this->arrowsUpFrame->destroy();
		$this->arrowsDownFrame->destroy();
		$this->elementsFrame->destroy();
	}
}

?>