<?php
/**
 * ManiaLib - Lightweight PHP framework for Manialinks
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLib\Gui\Cards\Dialogs;

/**
 * Dialog
 * Dialog box with 2 buttons
 */
class TwoButtons extends OneButton
{
	/**
	 * @var \ManiaLib\Gui\Elements\Button
	 */
	public $button2;
	
	function __construct($sizeX = 65, $sizeY = 25)
	{
		parent::__construct($sizeX, $sizeY);
		
		$this->button->setPosition(-15, 0, 0);
		
		$this->button2 = new \ManiaLib\Gui\Elements\Button;
		$this->button2->setPosition(15, 0, 0);
		$this->button2->setAlign('left', 'bottom');
		$this->addCardElement($this->button2);
	}
	
	function preFilter()
	{
		parent::preFilter();
		$this->button->setHalign('right');
		$this->button2->setPositionY(2 - $this->sizeY);
		$this->button->setPositionX(-2);
		$this->button2->setPositionX(2);
	}
}

?>