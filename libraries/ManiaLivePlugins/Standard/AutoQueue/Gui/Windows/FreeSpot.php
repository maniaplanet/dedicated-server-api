<?php
/**
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\Standard\AutoQueue\Gui\Windows;

use ManiaLib\Gui\Elements\Bgs1InRace;
use ManiaLib\Gui\Elements\Label;
use ManiaLivePlugins\Standard\AutoQueue\Config;

/**
 * Description of FreeSpot
 */
class FreeSpot extends \ManiaLive\Gui\Window
{
	function onConstruct()
	{
		$ui = new Bgs1InRace(40, 5.5);
		$ui->setSubStyle(Bgs1InRace::BgCardList);
		$ui->setHalign('center');
		$this->addComponent($ui);
		
		$ui = new Label(38, 5);
		$ui->setStyle(Label::TrackerTextBig);
		$ui->setHalign('center');
		$ui->setPosition(0, -1);
		$ui->setText('Free spot. Have fun !');
		$this->addComponent($ui);
		
		$this->setPosition(Config::getInstance()->posX, Config::getInstance()->posY - 29.5);
	}
	
	function onDraw()
	{
		$this->setTimeout(3);
	}
}

?>