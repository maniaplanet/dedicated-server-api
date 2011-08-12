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

/**
 * Zone card
 */ 
class Zone extends \ManiaLib\Gui\Elements\Quad
{
	/**
	 * @var \ManiaLib\Gui\Elements\Label
	 */
	public $name;
	/**
	 * @var \ManiaLib\Gui\Elements\Quad
	 */
	public $flag;
	
	function __construct($sizeX=28, $sizeY=5)
	{
		parent::__construct($sizeX,$sizeY);
		
		$this->setSubStyle(\ManiaLib\Gui\Elements\Bgs1::BgCard2);
		$this->cardElementsValign = 'center';
		
		$this->flag = new \ManiaLib\Gui\Elements\Quad($this->sizeY-1.5, $this->sizeY-1.5);
		$this->flag->setValign('center');
		$this->flag->setPositionX(3);
		$this->addCardElement($this->flag);
		
		$this->name = new \ManiaLib\Gui\Elements\Label($this->sizeX - $this->sizeY - 7);
		$this->name->setValign('center');
		$this->name->setStyle(\ManiaLib\Gui\Elements\Label::TextChallengeNameMedium);
		$this->name->setPositionX($this->sizeY+4);
		$this->addCardElement($this->name);
	}
}

?>