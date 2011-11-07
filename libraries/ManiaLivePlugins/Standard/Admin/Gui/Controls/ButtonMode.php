<?php
/**
 * Admin Plugin - Allow admins to configure server on the fly
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\Standard\Admin\Gui\Controls;

use ManiaLib\Gui\Elements\Bgs1InRace;
use ManiaLib\Gui\Elements\Icons128x32_1;
use ManiaLib\Gui\Elements\Label;

class ButtonMode extends \ManiaLive\Gui\Control
{
	private $background;
	
	function __construct($modeName, $subStyle)
	{
		$this->setSize(20, 20);

		$this->background = new Bgs1InRace(20, 20);
		$this->addComponent($this->background);

		$ui = new Label(20);
		$ui->setText($modeName);
		$ui->setPosition(10, -1);
		$ui->setHalign('center');
		$this->addComponent($ui);

		$ui = new Icons128x32_1(13);
		$ui->setAlign('center', 'center');
		$ui->setPosition(10, -13);
		$ui->setSubStyle($subStyle);
		$this->addComponent($ui);
	}
	
	function setAction($action)
	{
		$this->background->setAction($action);
	}
	
	function setSelected()
	{
		$this->background->setSubStyle(Bgs1InRace::NavButtonBlink);
	}
	
	function setNotSelected()
	{
		$this->background->setSubStyle(Bgs1InRace::BgTitleGlow);
	}
}

?>
