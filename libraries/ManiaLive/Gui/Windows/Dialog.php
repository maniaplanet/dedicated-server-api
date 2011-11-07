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

namespace ManiaLive\Gui\Windows;

use ManiaLib\Gui\Elements\Bgs1;
use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\Quad;
use ManiaLib\Gui\Layouts\Line;
use ManiaLive\Gui\Controls\ButtonResizable;
use ManiaLive\Gui\Controls\Frame;

/**
 * @author Florian Schnell
 */
final class Dialog extends \ManiaLive\Gui\Panel
{
	const YES    = 1;
	const NO     = 2;
	const RETRY  = 4;
	const APPLY  = 8;
	const OK     = 16;
	const CANCEL = 32;
	
	static protected $labels = array(
		self::YES => 'Yes',
		self::NO => 'No',
		self::RETRY => 'Retry',
		self::APPLY => 'Apply',
		self::OK => 'OK',
		self::CANCEL => 'Cancel'
	);
	
	protected $text;
	protected $options;
	
	protected $answer;
	protected $buttons;
	
	protected function onConstruct($sizeX=70, $sizeY=60)
	{
		parent::onConstruct($sizeX, $sizeY);
		$this->setBackgroundStyle(Bgs1::BgWindow2);
		
		$this->text = new Label();
		$this->text->setPosition(2, -17);
		$this->text->enableAutonewline();
		$this->addComponent($this->text);
		
		$this->options = new Frame(0, 0, new Line());
		$this->options->setHalign('center');
		$this->addComponent($this->options);
	}
	
	function onShow()
	{
		$this->text->setSize($this->sizeX - 4, $this->sizeY - 6);
		
		$this->options->setSizeX($this->sizeX);
		$this->options->getLayout()->setSizeX($this->sizeX);
		$this->options->setPosition($this->sizeX / 2, 8 - $this->sizeY);
		$this->options->clearComponents();
		
		$buttons = array();
		for($i = 1; $i <= $this->buttons; $i <<= 1)
			if($this->buttons & $i)
				$buttons[] = $i;
		$buttonsSizeX = ($this->sizeX - 2.5) / count($buttons);
		
		foreach($buttons as $button)
		{
			$ui = new ButtonResizable($buttonsSizeX, 7);
			$ui->setPosX(1.25);
			$ui->setText(self::$labels[$button]);
			$ui->setAction($this->createAction(array($this, 'onButton'), $button));
			$this->options->addComponent($ui);
		}
	}
	
	function setText($text)
	{
		$this->text->setText($text);
	}
	
	function onButton($login, $button)
	{
		$this->answer = $button;
		$this->hide();
	}
	
	function getAnswer()
	{
		return $this->answer;
	}
	
	function setButtons($buttons)
	{
		$this->buttons = $buttons;
	}
}

?>