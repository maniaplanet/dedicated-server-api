<?php
/**
 * MapVote Plugin - Is the current liked by players?
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\Standard\MapVote\Gui\Windows;

use ManiaLib\Gui\Elements\BgsPlayerCard;
use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\Quad;
use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\Standard\MapVote\MapVote;

class Vote extends \ManiaLive\Gui\Window
{
	/**
	 * @var \ManiaLivePlugins\Freezone\MapVote\MapVote
	 */
	static private $mapVote;
	public $currentVote;
	
	static private $background;
	static private $title;
	static private $description;
	static private $scoreImage;
	static private $score;
	static private $good;
	static private $bad;
	
	private $mark;
	
	const PIC_COUNT  = 6;
	const PIC_OFFSET = 17;
	const PIC_PATH   = 'http://files.manialive.com/icons';
	
	static function Initialize($mapVote)
	{
		self::$mapVote = $mapVote;
		
		self::$background = new BgsPlayerCard(53, 15);
		self::$background->setSubStyle(BgsPlayerCard::BgPlayerCardBig);
		
		self::$title = new Label(48, 3);
		self::$title->setPosition(2, -0.5);
		self::$title->setTextSize(3.5);
		self::$title->setText('Do you like this map?');
		self::$title->setSubStyle(Label::TextCardRaceRank);
		
		self::$description = new Label(40);
		self::$description->setHalign('right');
		self::$description->setTextSize(3.5);
		self::$description->setText('Track\'s Score');
		self::$description->setPosition(38, -16);
		
		self::$scoreImage = new Quad(9, 9);		
		self::$scoreImage->setAlign('center', 'center');
		self::$scoreImage->setPosition(43, -21);
		
		self::$score = new Label();
		self::$score->setHalign('right');
		self::$score->setTextSize(3.5);
		self::$score->setPosition(38, -21);
		
		self::$good = new Quad(7, 7);
		self::$good->setImage(self::GetScoreImage(1, 0), true);
		self::$good->setPosition(15, -6.5);
		self::$good->setAction(ActionHandler::getInstance()->createAction(array(self::$mapVote, 'voteGood'), true));
		
		self::$bad = new Quad(7, 7);
		self::$bad->setImage(self::GetScoreImage(0, 1), true);
		self::$bad->setPosition(28, -6.5);
		self::$bad->setAction(ActionHandler::getInstance()->createAction(array(self::$mapVote, 'voteBad'), true));
	}
	
	static function Update()
	{
		self::$scoreImage->setImage(self::GetScoreImage(self::$mapVote->score['good'], self::$mapVote->score['bad']), true);
		self::$score->setText(self::$mapVote->score['good'].' / '.self::$mapVote->score['bad']);
	}
	
	static function Unload()
	{
		ActionHandler::getInstance()->deleteAction(self::$good->getAction());
		ActionHandler::getInstance()->deleteAction(self::$bad->getAction());
		self::$mapVote = null;
		self::$background = null;
		self::$title = null;
		self::$description = null;
		self::$scoreImage = null;
		self::$score = null;
		self::$good = null;
		self::$bad = null;
	}
	
	protected function onConstruct()
	{
		$this->mark = new BgsPlayerCard(8, 8);
		$this->mark->setAlign('center', 'center');
		$this->mark->setPositionY(-10);
		$this->mark->setSubStyle(BgsPlayerCard::BgActivePlayerScore);
		
		$this->addComponent(self::$background);
		$this->addComponent(self::$title);
		$this->addComponent(self::$description);
		$this->addComponent($this->mark);
		$this->addComponent(self::$scoreImage);
		$this->addComponent(self::$score);
		$this->addComponent(self::$good);
		$this->addComponent(self::$bad);
	}

	function onDraw()
	{
		if($this->currentVote == MapVote::VOTE_GOOD)
		{
			$this->mark->setVisibility(true);
			$this->mark->setPositionX(18.5);
		}
		else if($this->currentVote == MapVote::VOTE_BAD)
		{
			$this->mark->setVisibility(true);
			$this->mark->setPositionX(31.5);
		}
		else
			$this->mark->setVisibility(false);
	}
	
	static private function GetScoreImage($good, $bad)
	{
		$percent = 0;
		
		if($bad == 0 && $good == 0)
			return self::PIC_PATH . '/thumb_051.dds';
		else if($bad == 0 && $good > 0)
			return self::PIC_PATH . '/thumb_100.dds';
		else if($good == 0 && $bad > 0)
			return self::PIC_PATH . '/thumb_000.dds';
		else
			$percent =  $good / ($good + $bad);
		
		// transform percent in a picture number
		$imageId = min(100, self::PIC_OFFSET * round(self::PIC_COUNT * $percent));
		return self::PIC_PATH.'/thumb_'.str_pad($imageId, 3, '0', STR_PAD_LEFT).'.dds';
	}
}

?>