<?php

namespace ManiaLivePlugins\Standard\MapVote\Gui\Windows;

use ManiaLib\Gui\Elements\Quad;

use ManiaLib\Gui\Elements\BgsPlayerCard;

use ManiaLivePlugins\Standard\MapVote\MapVote;

use ManiaLib\Gui\Elements\Label;

use ManiaLib\Gui\Elements\Icons64x64_1;

use ManiaLive\Gui\Windowing\Controls\Panel;

class Vote extends \ManiaLive\Gui\Windowing\Window
{
	/**
	 * @var \ManiaLivePlugins\Freezone\MapVote\MapVote
	 */
	static protected $mapVote;
	public $currentVote;
	
	static protected $quaBg;
	static protected $lblTitle;
	static protected $lblScoreDesc;
	
	protected $lblScore;
	protected $quaMark;
	protected $quaScore;
	protected $quaGood;
	protected $quaBad;
	
	const PIC_COUNT = 6;
	const PIC_OFFSET = 17;
	const PIC_PATH = 'http://files.manialive.com/icons';
	
	static function Initialize($mapVote)
	{
		self::$mapVote = $mapVote;
		
		self::$quaBg = new BgsPlayerCard(21, 7.5);
		self::$quaBg->setSubStyle(BgsPlayerCard::BgPlayerCardBig);
		
		self::$lblTitle = new Label(15, 3);
		self::$lblTitle->setPosition(2, 0.5);
		self::$lblTitle->setTextSize(2.5);
		self::$lblTitle->setText('Do you like this map?');
		self::$lblTitle->setSubStyle(Label::TextCardRaceRank);
		
		self::$lblScoreDesc = new Label();
		self::$lblScoreDesc->setHalign('right');
		self::$lblScoreDesc->setTextSize(2);
		self::$lblScoreDesc->setText('Track\'s Score');
		self::$lblScoreDesc->setPosition(13, 7.8);
	}
	
	static function Unload()
	{
		self::$mapVote = null;
		self::$quaBg = null;
		self::$lblTitle = null;
		self::$lblScoreDesc = null;
	}
	
	function initializeComponents()
	{
		$this->quaMark = new BgsPlayerCard(3.8, 3.8);
		$this->quaMark->setAlign('center', 'center');
		$this->quaMark->setPositionY(5);
		$this->quaMark->setSubStyle(BgsPlayerCard::BgActivePlayerScore);
		
		$this->quaScore = new Quad(4.5, 4.5);		
		$this->quaScore->setAlign('center', 'center');
		$this->quaScore->setPosition(16, 10);
		
		$this->quaGood = new Quad(2.7, 2.7);
		$this->quaGood->setImage($this->calculateScoreImage(1, 0), true);
		$this->quaGood->setPosition(4.8, 3.6);
		$this->quaGood->setAction($this->callback(array(self::$mapVote, 'voteGood'), true));
		
		$this->quaBad = new Quad(2.7, 2.7);
		$this->quaBad->setImage($this->calculateScoreImage(0, 1), true);
		$this->quaBad->setPosition(11.3, 3.6);
		$this->quaBad->setAction($this->callback(array(self::$mapVote, 'voteBad'), true));
		
		$this->lblScore = new Label();
		$this->lblScore->setHalign('right');
		$this->lblScore->setTextSize(3.7);
		$this->lblScore->setPosition(13, 9.65);
		
		$this->addComponent(self::$quaBg);
		$this->addComponent(self::$lblTitle);
		$this->addComponent(self::$lblScoreDesc);
		$this->addComponent($this->quaMark);
		$this->addComponent($this->quaScore);
		$this->addComponent($this->quaGood);
		$this->addComponent($this->quaBad);
		$this->addComponent($this->lblScore);
	}

	function onDraw()
	{
		$this->quaScore->setImage(
			$this->calculateScoreImage(
				self::$mapVote->score['good'],
				self::$mapVote->score['bad']
			),
			true
		);
		
		$this->lblScore->setText(self::$mapVote->score['good'].' / '.self::$mapVote->score['bad']);
		
		if ($this->currentVote == MapVote::VOTE_GOOD)
		{
			$this->quaMark->setVisibility(true);
			$this->quaMark->setPositionX(6.05);
		}
		elseif ($this->currentVote == MapVote::VOTE_BAD)
		{
			$this->quaMark->setVisibility(true);
			$this->quaMark->setPositionX(12.55);
		}
		else 
		{
			$this->quaMark->setVisibility(false);
		}
	}
	
	function calculateScoreImage($good, $bad)
	{
		$percent = 0;
		
		// both are zero
		if ($bad == 0 && $good == 0)
		{
			return self::PIC_PATH . '/thumb_051.dds';
		}
		
		// only bad is zero
		elseif ($bad == 0 && $good > 0)
		{
			return self::PIC_PATH . '/thumb_100.dds';
		}
		
		// only good is zero
		elseif ($good == 0 && $bad > 0)
		{
			return self::PIC_PATH . '/thumb_000.dds';
		}
		
		// we need calculation
		else
		{
			$percent =  $good / ($good + $bad);
		}
		
		// transform percent in a picture number
		$img_id = self::PIC_OFFSET * round(self::PIC_COUNT * $percent);
		if ($img_id > 100) $img_id = 100;
		
		return self::PIC_PATH . '/thumb_' . str_pad($img_id, 3, '0', STR_PAD_LEFT) . '.dds';
	}
}

?>