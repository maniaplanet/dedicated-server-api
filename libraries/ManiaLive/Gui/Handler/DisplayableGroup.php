<?php
namespace ManiaLive\Gui\Handler;

use ManiaLive\Utilities\Console;

use ManiaLive\Gui\Toolkit\Manialink;

use ManiaLive\Gui\Toolkit\Manialinks;

use ManiaLive\Gui\Toolkit;

/**
 *
 * @author Philippe Melot
 * @copyright NADEO (c) 2010
 * 
 */
class DisplayableGroup
{
	protected $displayables = array();
	protected $displayables_by_id = array();
	
	public $timeout;
	public $hideOnClick;

	protected $notice = null;
	protected $challengeInfo = null;
	protected $chat = null;
	protected $checkpointList = null;
	protected $roundScores = null;
	protected $scoretable = null;
	protected $global = null;
	protected $showCustomUi = false;
	
	function addDisplayable(Displayable $displayable)
	{
		if (isset($this->displayables_by_id[$displayable->getId()]))
		{
			$size = count($this->displayables);
			$found = 0;
			for ($i = 0; $i < $size; $i++)
			{
				if ($this->displayables[$i]->getId() == $displayable->getId())
				{
					array_splice($this->displayables, $i, 1);
					break;
				}
			}
		}
		
		$this->displayables[] = $displayable;
		$this->displayables_by_id[$displayable->getId()] = true;
	}
	
	function getDisplayables()
	{
		return $this->displayables;
	}

	function getXml($login)
	{
		Manialinks::load();

		foreach($this->displayables as $displayable)
		{
			Manialinks::beginManialink(
				$displayable->getPosX() / 64, $displayable->getPosY() / -64, $displayable->getPosZ(),
				$displayable->getId()
			);
			{
				Manialink::setNormalPositioning();
				$displayable->display($login);
			}
			Manialinks::endManialink();
		}

		//TODO find a way to make this optionnal
		if($this->showCustomUi)
		{
			Manialinks::beginCustomUi();
			{
				if($this->notice !== null)
				Manialinks::setNoticeVisibility($this->notice);
				if($this->challengeInfo !== null)
				Manialinks::setChallengeInfoVisibility($this->challengeInfo);
				if($this->chat !== null)
				Manialinks::setChatVisibility($this->chat);
				if($this->checkpointList !== null)
				Manialinks::setCheckpointListVisibility($this->checkpointList);
				if($this->roundScores !== null)
				Manialinks::setRoundScoresVisibility($this->roundScores);
				if($this->scoretable !== null)
				Manialinks::setScoretableVisibility($this->scoretable);
				if($this->global !== null)
				Manialinks::setGlobalVisibility($this->global);
			}
			Manialinks::endCustomUi();
		}
		return Manialinks::getXml();
	}

	function showNoticeUi()
	{
		$this->changeVisibility('notice', true);
	}

	function hideNoticeUi()
	{
		$this->changeVisibility('notice', false);
	}

	function showChallengeInfoUi()
	{
		$this->changeVisibility('challengeInfo', true);
	}

	function hideChallengeInfoUi()
	{
		$this->changeVisibility('challengeInfo', false);
	}

	function showChatUi()
	{
		$this->changeVisibility('chat', true);
	}

	function hideChatUi()
	{
		$this->changeVisibility('chat', false);
	}

	function showCheckpointListUi()
	{
		$this->changeVisibility('checkpointList', true);
	}

	function hideCheckpointListUi()
	{
		$this->changeVisibility('checkpointList', false);
	}

	function showRoundScoresUi()
	{
		$this->changeVisibility('roundScores', true);
	}

	function hideRoundScoresUi()
	{
		$this->changeVisibility('roundScores', false);
	}

	function showScoretableUi()
	{
		$this->changeVisibility('scoretable', true);
	}

	function hideScoretableUi()
	{
		$this->changeVisibility('scoretable', false);
	}

	function showGlobalUi()
	{
		$this->changeVisibility('global', true);
	}

	function hideGlobalUi()
	{
		$this->changeVisibility('global', false);
	}

	protected function changeVisibility($property, $value)
	{
		$this->$property = $value;
		$this->showCustomUi = true;
	}
}