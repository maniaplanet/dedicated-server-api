<?php
/**
 * @author Maxime Raoust
 * @copyright 2009-2010 NADEO 
 * @package ManiaMod
 */
namespace ManiaLive\Gui\Toolkit\Cards;

use ManiaLive\Gui\Toolkit as Toolkit;
use ManiaLive\Gui\Toolkit\Elements as Elements;

/**
 * Challenge card
 * Just like challenge cards that can be found when you browse TrackMania's campaigns
 */ 
class ChallengeCard extends Elements\Quad
{
	public $bgImage;
	public $text;
	public $points;
	public $lockedMessage;
	protected $showArrow = false;
	
	protected $sizeX = 16;
	protected $sizeY = 17;
	protected $medal;
	
	protected $clickable = true;
	protected $clickableMask;
	protected $clickableLock;
	
	function __construct ()
	{
		$this->setStyle(Elements\Quad::BgsChallengeMedals);
		$this->setSubStyle(Elements\BgsChallengeMedals::BgNotPlayed);
		
		$this->bgImage = new Elements\Quad(15, 13.5);
		$this->bgImage->setHalign("center");
		$this->bgImage->setPosition(0, -0.5, 0);
		
		$this->points = new Elements\Label(9);
		$this->points->setPosition(-6.5, -10.75, 2);
		
		$this->text = new Elements\Label(15);
		$this->text->setPosition(0, -14, 4);
		$this->text->setHalign("center");
		$this->text->setStyle(Elements\Label::TextChallengeNameSmall);
		
		$this->lockedMessage = new Elements\Label(13);
		$this->lockedMessage->setPosition(0, -1.5, 2);
		$this->lockedMessage->setHalign("center");
		$this->lockedMessage->enableAutonewline();
		$this->lockedMessage->setStyle(Elements\Label::TextRaceChat);
		
		$this->clickableMask = new Elements\Quad($this->sizeX, $this->sizeY);
		$this->clickableMask->setHalign("center");
		$this->clickableMask->setPositionZ(1);
		$this->clickableMask->setStyle(Elements\Quad::BgsPlayerCard);
		$this->clickableMask->setSubStyle(Elements\BgsPlayerCard::BgPlayerName);
		
		$this->clickableLock = new Elements\Icons128x128_1(7.5);
		$this->clickableLock->setPosition(8, -14, 2);
		$this->clickableLock->setAlign("right", "bottom");
		$this->clickableLock->setSubStyle(Elements\Icons128x128_1::Padlock);
	}
	
	/**
	 * Whether to show the blue arrow above the challenge icon
	 * @param boolean
	 */
	function showArrow($show = true)
	{
		$this->showArrow = $show;
	}
	
	/**
	 * Sets the element un-clickable, dims the challenge icon and displays a
	 * lock icon
	 */
	function setUnclickable()
	{
		$this->clickable = false;
	}
	
	protected function preFilter()
	{
		$this->setPositionZ($this->posZ+3);
		
		if($this->showArrow) 
		{
			$this->setImage("BgsChallengeRace.dds");
		}
	}
	
	protected function postFilter()
	{
		// Algin the title and its bg at the top center of the main quad		
		$arr = Toolkit\Tools::getAlignedPos ($this, "center", "top");
		$x = $arr["x"];
		$y = $arr["y"];
		
		Toolkit\Manialink::beginFrame($x, $y, $this->posZ-3);
			$this->bgImage->save();
			$this->points->save();		
			if(!$this->clickable)
			{
				$this->clickableMask->save();
				$this->clickableLock->save();
				$this->lockedMessage->save();
			}
			$this->text->save();
		Toolkit\Manialink::endFrame();
	}
}

?>