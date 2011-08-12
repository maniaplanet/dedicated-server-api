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

namespace ManiaLive\Gui\Handler;

use ManiaLive\Utilities\Console;
use ManiaLib\Gui\Manialink;
use ManiaLive\Gui\Handler\Manialinks;

class DisplayableGroup
{
	protected $displayables = array();
	protected $displayablesById = array();
	
	public $timeout;
	public $hideOnClick;

	public $notice = null;
	public $challengeInfo = null;
	public $chat = null;
	public $checkpointList = null;
	public $roundScores = null;
	public $scoretable = null;
	public $global = null;
	public $showCustomUi = false;
	
	/**
	 * Add a Displayable to the group
	 * @param Displayable $displayable
	 */
	function addDisplayable(Displayable $displayable)
	{
		if (isset($this->displayablesById[$displayable->getId()]))
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
		$this->displayablesById[$displayable->getId()] = true;
	}
	
	/**
	 * Get the displayables
	 * @return array[Displayable]
	 */
	function getDisplayables()
	{
		return $this->displayables;
	}

	/**
	 * Return the XML of the displayable for the given login
	 * @param $login
	 * @return string
	 */
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

	/**
	 * Show Notice graphique interface
	 */
	function showNoticeUi()
	{
		$this->changeVisibility('notice', true);
	}

	/**
	 * Hide Notice graphique interface
	 */
	function hideNoticeUi()
	{
		$this->changeVisibility('notice', false);
	}

	/**
	 * Show Challenge Infos
	 */
	function showChallengeInfoUi()
	{
		$this->changeVisibility('challengeInfo', true);
	}

	/**
	 * Hide Challenge Infos
	 */
	function hideChallengeInfoUi()
	{
		$this->changeVisibility('challengeInfo', false);
	}

	/**
	 * Show Chat
	 */
	function showChatUi()
	{
		$this->changeVisibility('chat', true);
	}

	/**
	 * Hide Chat
	 */
	function hideChatUi()
	{
		$this->changeVisibility('chat', false);
	}

	/**
	 * Show Checkpoint List
	 */
	function showCheckpointListUi()
	{
		$this->changeVisibility('checkpointList', true);
	}

	/**
	 * Hide Checkpoint List
	 */
	function hideCheckpointListUi()
	{
		$this->changeVisibility('checkpointList', false);
	}

	/**
	 * Show Round Score
	 */
	function showRoundScoresUi()
	{
		$this->changeVisibility('roundScores', true);
	}

	/**
	 * Hide Round Score
	 */
	function hideRoundScoresUi()
	{
		$this->changeVisibility('roundScores', false);
	}

	/**
	 * Show Score table
	 */
	function showScoretableUi()
	{
		$this->changeVisibility('scoretable', true);
	}

	/**
	 * Hide Score table
	 */
	function hideScoretableUi()
	{
		$this->changeVisibility('scoretable', false);
	}

	/**
	 * Show the entire interface
	 */
	function showGlobalUi()
	{
		$this->changeVisibility('global', true);
	}

	/**
	 * Hide the entire interface
	 */
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