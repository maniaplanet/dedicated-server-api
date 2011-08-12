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
 * Bullet
 * Bullet to make nice lists
 */
class Bullet extends \ManiaLib\Gui\Elements\Spacer
{
	/**
	 * @var \ManiaLib\Gui\Elements\Quad
	 */
	public $bg;
	/**
	 * @var \ManiaLib\Gui\Elements\Icon
	 */
	public $bullet;
	/**
	 * @var \ManiaLib\Gui\Elements\Label
	 */
	public $title;
	
	function __construct($sizeX = 50, $sizeY = 8)
	{
		parent::__construct($sizeX, $sizeY);
		
		$this->cardElementsValign = 'center';
		
		$this->bg = new \ManiaLib\Gui\Elements\Quad($sizeX, 5);
		$this->bg->setValign('center');
		$this->bg->setSubStyle(\ManiaLib\Gui\Elements\Bgs1::BgList);
		$this->addCardElement($this->bg);
		
		$this->bullet = new \ManiaLib\Gui\Elements\Icon(8);
		$this->bullet->setValign('center');
		$this->bullet->setPosition(0.5, -0.1, 1);
		$this->addCardElement($this->bullet);
		
		$this->title = new \ManiaLib\Gui\Elements\Label();
		$this->title->setValign('center');
		$this->title->setPosition(0, 0.1, 1);
		$this->title->setStyle(\ManiaLib\Gui\Elements\Label::TextTitle3);
		$this->addCardElement($this->title);
	}
	
	/**
	 * @ignore
	 */
	protected function preFilter()
	{
		$this->title->setPositionX($this->title->getPosX() + 9.5);
		$this->title->setSizeX($this->getSizeX() - $this->title->getPosX() - 2);
	}
}
?>