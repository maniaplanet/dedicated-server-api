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
		
		self::$quaBg = new BgsPlayerCard(53, 15);
		self::$quaBg->setSubStyle(BgsPlayerCard::BgPlayerCardBig);
		
		self::$lblTitle = new Label(48, 3);
		self::$lblTitle->setPosition(2, 0.5);
		self::$lblTitle->setTextSize(3.5);
		self::$lblTitle->setText('Do you like this map?');
		self::$lblTitle->setSubStyle(Label::TextCardRaceRank);
		
		self::$lblScoreDesc = new Label(40);
		self::$lblScoreDesc->setHalign('right');
		self::$lblScoreDesc->setTextSize(3.5);
		self::$lblScoreDesc->setText('Track\'s Score');
		self::$lblScoreDesc->setPosition(38, 16);
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
		$this->quaMark = new BgsPlayerCard(8, 8);
		$this->quaMark->setAlign('center', 'center');
		$this->quaMark->setPositionY(10);
		$this->quaMark->setSubStyle(BgsPlayerCard::BgActivePlayerScore);
		
		$this->quaScore = new Quad(9, 9);		
		$this->quaScore->setAlign('center', 'center');
		$this->quaScore->setPosition(43, 21);
		
		$this->quaGood = new Quad(7, 7);
		$this->quaGood->setImage($this->calculateScoreImage(1, 0), true);
		$this->quaGood->setPosition(15, 6.5);
		$this->quaGood->setAction($this->callback(array(self::$mapVote, 'voteGood'), true));
		
		$this->quaBad = new Quad(7, 7);
		$this->quaBad->setImage($this->calculateScoreImage(0, 1), true);
		$this->quaBad->setPosition(28, 6.5);
		$this->quaBad->setAction($this->callback(array(self::$mapVote, 'voteBad'), true));
		
		$this->lblScore = new Label();
		$this->lblScore->setHalign('right');
		$this->lblScore->setTextSize(3.5);
		$this->lblScore->setPosition(38, 21);
		
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
			$this->quaMark->setPositionX(18.5);
		}
		elseif ($this->currentVote == MapVote::VOTE_BAD)
		{
			$this->quaMark->setVisibility(true);
			$this->quaMark->setPositionX(31.5);
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