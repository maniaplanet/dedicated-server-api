<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\Gui\Toolkit\Elements;

/**
 * Format
 */
class Format extends Element
{
	/**#@+
	 * Manialink <b>styles</b> for the <b>Format</b> element and its children
	 */
	const TextButtonBig               = 'TextButtonBig';
	const TextButtonMedium            = 'TextButtonMedium';
	const TextButtonNav               = 'TextButtonNav';
	const TextButtonSmall             = 'TextButtonSmall';
	const TextCardInfoSmall           = 'TextCardInfoSmall';
	const TextCardMedium              = 'TextCardMedium';
	const TextCardRaceRank            = 'TextCardRaceRank';
	const TextCardScores2             = 'TextCardScores2';
	const TextCardSmallScores2        = 'TextCardSmallScores2';
	const TextCardSmallScores2Rank    = 'TextCardSmallScores2Rank';
	const TextChallengeNameMedal      = 'TextChallengeNameMedal';
	const TextChallengeNameMedalNone  = 'TextChallengeNameMedalNone';
	const TextChallengeNameMedium     = 'TextChallengeNameMedium';
	const TextChallengeNameSmall      = 'TextChallengeNameSmall';
	const TextCongratsBig             = 'TextCongratsBig';
	const TextCredits                 = 'TextCredits';
	const TextCreditsTitle            = 'TextCreditsTitle';
	const TextInfoMedium              = 'TextInfoMedium';
	const TextInfoSmall               = 'TextInfoSmall';
	const TextPlayerCardName          = 'TextPlayerCardName';
	const TextRaceChat                = 'TextRaceChat';
	const TextRaceChrono              = 'TextRaceChrono';
	const TextRaceChronoError         = 'TextRaceChronoError';
	const TextRaceChronoWarning       = 'TextRaceChronoWarning';
	const TextRaceMessage             = 'TextRaceMessage';
	const TextRaceStaticSmall         = 'TextRaceStaticSmall';
	const TextRaceValueSmall          = 'TextRaceValueSmall';
	const TextRankingsBig             = 'TextRankingsBig';
	const TextStaticMedium            = 'TextStaticMedium';
	const TextStaticSmall             = 'TextStaticSmall';
	const TextSubTitle1               = 'TextSubTitle1';
	const TextSubTitle2               = 'TextSubTitle2';
	const TextTips                    = 'TextTips';
	const TextTitle1                  = 'TextTitle1';
	const TextTitle2                  = 'TextTitle2';
	const TextTitle3                  = 'TextTitle3';
	const TextTitle2Blink             = 'TextTitle2Blink';
	const TextTitleError              = 'TextTitleError';
	const TextValueBig                = 'TextValueBig';
	const TextValueMedium             = 'TextValueMedium';
	const TextValueSmall              = 'TextValueSmall';
	/**#@-*/

	protected $xmlTagName = 'format';
	protected $halign = null;
	protected $valign = null;
	protected $posX = null;
	protected $posY = null;
	protected $posZ = null;
	protected $style = null;
	protected $subStyle = null;
	protected $textSize;
	protected $textColor;

	function __construct()
	{
	}

	/**
	 * Sets the text size
	 * @param int
	 */
	function setTextSize($textsize)
	{
		$this->textSize = $textsize;
		$this->setStyle(null);
		$this->setSubStyle(null);
	}

	/**
	 * Sets the text color
	 * @param string 3-digit RGB hexadecimal value
	 */
	function setTextColor($textcolor)
	{
		$this->textColor = $textcolor;
		$this->setStyle(null);
		$this->setSubStyle(null);
	}

	/**
	 * Returns the text size
	 * @return int
	 */
	function getTextSize()
	{
		return $this->textSize;
	}

	/**
	 * Returns the text color
	 * @return string 3-digit RGB hexadecimal value
	 */
	function getTextColor()
	{
		return $this->textColor;
	}

	protected function postFilter()
	{
		if($this->textSize !== null)
		{
			$this->xml->setAttribute('textsize', $this->textSize);
		}
		if($this->textColor !== null)
		{
			$this->xml->setAttribute('textcolor', $this->textColor);
		}
	}
}

?>