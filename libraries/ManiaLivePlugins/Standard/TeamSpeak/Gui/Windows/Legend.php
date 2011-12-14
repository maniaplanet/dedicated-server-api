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
use ManiaLive\Gui\Controls\ButtonResizable;
use ManiaLivePlugins\Standard\TeamSpeak\Gui\Images;

/**
 * Description of Legend
 */
class Legend extends \ManiaLive\Gui\Window
{
	protected function onConstruct()
	{
		$ui = new Quad(90, 67);
		$ui->setBgcolor('0008');
		$this->addComponent($ui);
		
		$ui = new Label(50);
		$ui->setStyle(Label::TextRankingsBig);
		$ui->setHalign('center');
		$ui->setPosition(45, -2);
		$ui->setText('Legend');
		$this->addComponent($ui);
		
		$images = Images::getInstance();
		$sections = array(
			'$282Channels' => array(
				$images->channelCommentOff => 'Not moderated: everybody can talk on this channel',
				$images->channelCommentOn => 'Moderated: only commmentators can talk'
			),
			'$228Clients' => array(
				$images->clientCommentNeutral => 'Guest on a non-moderated channel',
				$images->clientCommentOff => 'Guest on a moderated channel',
				$images->clientCommentOn => 'Commentator',
				null => 'A darker line means the client is away'
			)
		);
		
		$yIndex = -10;
		foreach($sections as $name => $bullets)
		{
			$ui = new Label(50);
			$ui->setStyle(Label::TrackerTextBig);
			$ui->setPosition(3, $yIndex);
			$ui->setText($name);
			$this->addComponent($ui);
			
			$yIndex -= 5;
			foreach($bullets as $image => $text)
			{
				if($image)
				{
					$ui = new Quad(5, 5);
					$ui->setBgcolor('0008');
					$ui->setPosition(2, $yIndex);
					$this->addComponent($ui);

					$ui = new Quad(5, 5);
					$ui->setImage($image, true);
					$ui->setPosition(2, $yIndex);
					$this->addComponent($ui);
				}
				
				$ui = new Quad(80.5, 5);
				$ui->setBgcolor('0008');
				$ui->setPosition(7.5, $yIndex);
				$this->addComponent($ui);
				
				$ui = new Label(78.5);
				$ui->setTextSize(1);
				$ui->setValign('center2');
				$ui->setPosition(8.5, $yIndex - 2.5);
				$ui->setText($text);
				$this->addComponent($ui);
				
				$yIndex -= 5.5;
			}
			
			$yIndex -= 2;
		}
		
		$ui = new ButtonResizable(20, 7);
		$ui->setHalign('center');
		$ui->setPosition(45, $yIndex - 1);
		$ui->setAction($this->createAction(array($this, 'hide')));
		$ui->setText('OK');
		$this->addComponent($ui);
		
		$this->setSize(90, 67);
		$this->centerOnScreen();
	}
}

?>