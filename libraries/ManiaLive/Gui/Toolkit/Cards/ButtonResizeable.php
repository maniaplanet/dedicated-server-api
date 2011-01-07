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

namespace ManiaLive\Gui\Toolkit\Cards;

use ManiaLive\Gui\Toolkit\Elements as Elements;

/**
 * A button that can be resized gridless.
 * @author Florian Schnell
 * @copyright 2010 NADEO
 */
class ButtonResizeable extends Elements\Quad
{
	public $label;
	
	function __construct($size_x = 20, $size_y = 5)
	{
		parent::__construct($size_x, $size_y);
		$this->setSubStyle(Elements\Bgs1InRace::BgButton);
		$this->label = new Elements\Label();
	}
	
	function preFilter()
	{
		$ui = $this->label;
		$ui->setSize($this->sizeX, $this->sizeY);
		$ui->setHalign('center');
		$ui->setValign('center');
		$ui->setPosition($this->posX + $this->sizeX/2, $this->posY - $this->sizeY/2, $this->posZ + 0.1);
		$ui->setTextColor('000');
		$ui->save();
	}
}
?>