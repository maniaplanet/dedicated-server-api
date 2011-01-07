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

use ManiaLive\Gui\Toolkit\Elements\Quad;
use ManiaLive\Gui\Toolkit\Manialink;
use ManiaLive\Gui\Displayables\Advanced;
use ManiaLive\Gui\Toolkit\Elements\Bgs1InRace;
use ManiaLive\Gui\Toolkit\Elements\BgsPlayerCard;
use ManiaLive\Gui\Toolkit\Elements\BgsChallengeMedals;
use ManiaLive\Gui\Toolkit\Elements\Bgs1;
use ManiaLive\Gui\Toolkit\Elements\Label;
use ManiaLive\Gui\Toolkit\Elements\Button;
use ManiaLive\Gui\Toolkit\Cards\ButtonResizeable;
use ManiaLive\Gui\Toolkit\Cards\Panel;
use ManiaLive\Gui\Handler\Displayable;

class DialogButton
{
	const OK = 1;
	const CANCEL = 2;
	const YES = 4;
	const NO = 8;
	const RETRY = 16;
	const APPLY = 32;
}

/**
 * You can use the advanced interface for handling
 * clicks, if you implement a listener for the click event
 * and forward it to the onClicked method of the Dialog.
 * @author Florian Schnell
 * @copyright 2010 NADEO
 */
class Dialog extends Advanced
{
	private $width;
	private $height;
	private $text;
	private $z;
	private $buttons;
	private $title;
	private $callbacks;
	private $on_ok;
	private $on_cancel;
	private $on_no;
	private $on_yes;
	private $on_apply;
	private $on_retry;
	private $number;
	private $blurred;
	
	const width_min = 33;
	const height_min = 20;
	const button_width = 15;
	const button_spacing = 1;
	
	static $count = 0;
	
	function setTitle($title)
	{
		$this->title = $title;
	}
	
	function getTitle()
	{
		return $this->title;
	}
	
	function setText($text)
	{
		$this->text = $text;
	}
	
	function getText()
	{
		return $this->text;
	}
	
	function setButtons($code)
	{
		$this->buttons = $code;
	}
	
	function blurBackground($blur)
	{
		$this->blurred = $blur;
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param $btn_id
	 * @param $method
	 */
	function setCallback($btn_id, $method)
	{
		switch ($btn_id)
		{
			case DialogButton::OK:
				$this->callbacks['ok'] = $method;
				break;
			case DialogButton::CANCEL:
				$this->callbacks['cancel'] = $method;
				break;
			case DialogButton::NO:
				$this->callbacks['no'] = $method;
				break;
			case DialogButton::YES:
				$this->callbacks['yes'] = $method;
				break;
			case DialogButton::APPLY:
				$this->callbacks['apply'] = $method;
				break;
			case DialogButton::RETRY:
				$this->callbacks['retry'] = $method;
				break;
		}
	}
	
	function setSize($width, $height)
	{
		// set width
		if ($width < self::width_min)
		{
			$width = self::width_min;
		}
			$this->width = $width;
			
		// set height
		if ($height < self::height_min)
		{
			$height = self::height_min;
		}
			$this->height = $height;
	}
	
	function init()
	{
		$this->width = 40;
		$this->height = 30;
		$this->plugin = null;
		$this->buttons = 3;
		$this->blurred = false;
		$this->z = self::$count;
		echo $this->z.",";
		echo self::$count.",";
	}
	
	function hide($login)
	{
		self::$count--;
	}
	
	function display($login)
	{
		if (!GuiHandler::isDisplayed($this->getID(), $login))
		{
			$this->number = ++self::$count;
		}
		
		$btnc = $this->buttons;
		
		// count buttons ...
		$btn_num = 0;
		for ($i = 32; $i >= 1; $i = floor($i/2))
		{
			if ($btnc - $i >= 0)
			{
				$btnc -= $i;
				$btn_num++;
			}
		}
		
		$btnc = $this->buttons;
		
		// set min window height ...
		if ($this->height < self::height_min)
		{
			$this->height = self::height_min;
		}
		
		// calculate space for one button ...
		$width_min = (self::button_width * $btn_num) + self::button_spacing * 2;
		if ($width_min > $this->width)
		{
			$this->width = $width_min;
		}
		
		// calculate space between buttons ...
		$btn_spacing = (($this->width - self::button_spacing * 2) / $btn_num) - self::button_width;
		
		// enable links ...
		Manialink::enableLinks();
		
		// BUTTONS
		$left = 1;
		for ($i = 0; $i < $btn_num; $i++)
		{
			// which button?
			for ($c = 32; $c > 0; $c = floor($c/2))
			{
				if ($btnc - $c >= 0)
				{
					$btnc -= $c;
					$button = $c;
					break;
				}
			}
				
			// configure button ...
			switch ($button)
			{
				case DialogButton::OK:
					$action = 'ok';
					$label = 'OK';
					$offset = 5;
					break;
				case DialogButton::CANCEL:
					$action = 'cancel';
					$label = ' Cancel ';
					$offset = 2.7;
					break;
				case DialogButton::YES:
					$action = 'yes';
					$label = ' Yes ';
					$offset = 5;
					break;
				case DialogButton::NO:
					$action = 'no';
					$label = ' No ';
					$offset = 5;
					break;
				case DialogButton::RETRY:
					$action = 'retry';
					$label = ' Retry ';
					$offset = 3.3;
					break;
				case DialogButton::APPLY:
					$action = 'apply';
					$label = ' Apply ';
					$offset = 3.2;
					break;
				default:
					$action = 'undef';
					$label = 'UNDEF';
					$offset = 5;
					break;
			}
			
			// create button ...
			$ui = new ButtonResizeable(self::button_width, 5);
			$ui->label->setText($label);
			$ui->setPosition(-$this->width / 2 + $left, -$this->height / 2 + 6, $this->z + 0.6);
			$ui->setAction($this->createAction($action));
			$ui->save();
			
			$left += self::button_width + $btn_spacing + ($btn_spacing / $btn_num) * 2;
		}
		
		// FORM
		$ui = new Panel($this->width, $this->height);
		$ui->title->setText($this->title);
		$ui->setPosition(-$this->width / 2, $this->height / 2, $this->z + 0.3);
		$ui->save();
		
		// CONTENT
		$ui = new Label($this->width - 8, $this->height - 6);
		$ui->setText($this->text);
		$ui->setTextSize(3);
		$ui->setTextColor('fff');
		$ui->enableAutonewline();
		$ui->setPosition(-$this->width / 2 + 2.5, $this->height / 2 - 6, $this->z + 0.6);
		$ui->save();
		
		// darken background
		if ($this->blurred)
		{
			$ui = new Bgs1(130, 100);
		}
		else
		{
			$ui = new Bgs1InRace(130, 100);
		}
			
		if ($this->blurred)
		{
			$ui->setSubStyle(Bgs1::NavButton);
		}
		else
		{
			$ui->setSubStyle(Bgs1InRace::BgWindow3);
		}
		$ui->setHalign('center');
		$ui->setValign('center');
		$ui->setPosition(0, 0, $this->z);
		$ui->save();
		
		// disable links ...
		Manialink::disableLinks();
	}
	
	function onClicked($playerUid, $login, $action, $plugin)
	{
		if (self::$count != $this->number)
		{
			return;
		}
		if (!array_key_exists($action, $this->callbacks))
		{
			return;
		}
		$callback_func = array($plugin, $this->callbacks[$action]);
		if (is_callable($callback_func))
		{
			call_user_func($callback_func, $playerUid, $login, $this);
		}
	}
}

?>