<?php 
/**
 * ManiaLib - Lightweight PHP framework for Manialinks
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLib\Gui\Elements;

/**
 * Format
 */
class Format extends \ManiaLib\Gui\Element
{
	/**#@+
	 * Manialink <b>styles</b> for the <b>Format</b> element and its children 
	 */
	const AvatarButtonNormal          = 'AvatarButtonNormal';
	const StyleTextScriptEditor       = 'StyleTextScriptEditor';
	const TextButtonBig               = 'TextButtonBig';
	const TextButtonMedium            = 'TextButtonMedium';
	const TextButtonNav               = 'TextButtonNav';
	const TextButtonNavBack           = 'TextButtonNavBack';
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
	const TextRaceMessageBig          = 'TextRaceMessageBig';
	const TextRaceStaticSmall         = 'TextRaceStaticSmall';
	const TextRaceValueSmall          = 'TextRaceValueSmall';
	const TextRankingsBig             = 'TextRankingsBig';
	const TextStaticMedium            = 'TextStaticMedium';
	const TextStaticSmall             = 'TextStaticSmall';
	const TextStaticVerySmall         = 'TextStaticVerySmall';
	const TextSubTitle1               = 'TextSubTitle1';
	const TextSubTitle2               = 'TextSubTitle2';
	const TextTips                    = 'TextTips';
	const TextTitle1                  = 'TextTitle1';
	const TextTitle2                  = 'TextTitle2';
	const TextTitle2Blink             = 'TextTitle2Blink';
	const TextTitle3                  = 'TextTitle3';
	const TextTitleError              = 'TextTitleError';
	const TextValueBig                = 'TextValueBig';
	const TextValueMedium             = 'TextValueMedium';
	const TextValueSmall              = 'TextValueSmall';
	const TrackListItem               = 'TrackListItem';
	const TrackListLine               = 'TrackListLine';
	const TrackerText                 = 'TrackerText';
	const TrackerTextBig              = 'TrackerTextBig';
	/**#@-*/
	
	/**#@+
	 * @ignore
	 */
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
	/**#@-*/

	function __construct() {}
	
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

	/**
	 * @ignore
	 */
	protected function postFilter()
	{
		if($this->textSize !== null)
			$this->xml->setAttribute('textsize', $this->textSize);
		if($this->textColor !== null)
			$this->xml->setAttribute('textcolor', $this->textColor);
	}
}

?>