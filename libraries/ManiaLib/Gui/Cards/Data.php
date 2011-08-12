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

namespace ManiaLib\Gui\Cards;

use ManiaLib\Gui\Elements\Label;
use ManiaLib\Log\Logger;
use ManiaLib\Utils\Arrays;
use ManiaLib\Utils\TMStrings;
use ManiaLib\Gui\Layouts\Column;
use ManiaLib\Gui\Manialink;
use ManiaLib\Gui\Elements\Bgs1;

/**
 * A simple card to display an array of (title, label) inside a Bgs1 quad
 */
class Data extends Bgs1
{

	/**
	 * Array of (title, label)
	 */
	public $data = array();

	function __construct($sizeX = 70, $sizeY = 0)
	{
		parent::__construct($sizeX, $sizeY);

		$this->setSubStyle(Bgs1::BgList);

		$this->cardElementsPosX = 2;
		$this->cardElementsPosY = -2;
		$this->cardElementsLayout = new Column();
	}

	function addData(array $data)
	{
		$this->data = array_merge($this->data, $data);
	}

	function preFilter()
	{
		foreach($this->data as $data)
		{
			$ui = new Label($this->sizeX - $this->cardElementsPosX * 2, 3.25);
			$ui->setText(TMStrings::formatLine(
					Arrays::get($data, 0, ''),
					Arrays::get($data, 1, '')
			));
			$this->addCardElement($ui);
		}
		$this->setSizeY($this->sizeY + count($this->data) * 3.25 - $this->cardElementsPosY * 2);
	}

}

?>