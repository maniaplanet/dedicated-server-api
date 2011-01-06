<?php
/**
 * @author Maxime Raoust
 * @copyright 2009-2010 NADEO 
 */

namespace ManiaLive\Gui\Toolkit;

use ManiaLive\Gui\Toolkit\Elements\Label;
use ManiaLive\Gui\Toolkit\Elements\Quad;
use ManiaLive\Gui\Toolkit\Elements\Bgs1;
use ManiaLive\Gui\Toolkit\Elements\Bgs1InRace;
use ManiaLive\Gui\Toolkit\Elements\Icons128x128_1;
use ManiaLive\Gui\Toolkit\Elements\Icons64x64_1;
use ManiaLive\Gui\Toolkit\Elements\MedalsBig;
use ManiaLive\Gui\Toolkit\Elements\Icons128x32_1;
use ManiaLive\Gui\Toolkit\Elements\Entry;
use ManiaLive\Gui\Toolkit\Elements\Button;

/**
 * Default element styles
 */
abstract class DefaultStyles
{
	const Quad_Style = Quad::Bgs1;
	const Quad_Substyle = Bgs1::BgWindow1;
	const Icon_Style = Quad::Icons128x128_1;
	const Icon_Substyle = Icons128x128_1::United;
	const Icon64_Style = Quad::Icons64x64_1;
	const Icon64_Substyle = Icons64x64_1::GenericButton;
	const Icon128_Style = Quad::Icons128x128_1;
	const Icon128_Substyle = Icons128x32_1::RT_Cup;
	const IconMedal_Style = Quad::MedalsBig;
	const IconMedal_Substyle = MedalsBig::MedalSlot;
	const Label_Style = Label::TextStaticSmall;
	const Entry_Style = Entry::TextValueSmall;
	const Button_Style = Button::CardButttonMedium;
	
	/**#@+
	 * Default styles for the Panel card
	 */
	const Panel_Style = Quad::Bgs1;
	const Panel_Substyle = Bgs1::BgWindow2;
	const Panel_Title_Style = Label::TextTitle3;
	const Panel_TitleBg_Style = Quad::Bgs1;
	const Panel_TitleBg_Substyle = Bgs1::BgTitle3_1;
	/**#@-*/
	
	/**#@+
	 * Default styles for NavigationButton card
	 */
	const NavigationButton_Style = Quad::Bgs1;
	const NavigationButton_Substyle = Bgs1::NavButton;
	const NavigationButton_Text_Style = Label::TextButtonNav;
	const NavigationButton_Selected_Substyle = Bgs1::NavButtonBlink;
	const NavigationButton_Selected_Text_Style = Label::TextButtonNav;
	/**#@-*/
	
	/**#@+
	 * Default styles for Navigation card
	 */
	const Navigation_Style = Quad::Bgs1;
	const Navigation_Substyle = Bgs1::BgWindow1;
	const Navigation_Title_Style = Label::TextRankingsBig;
	const Navigation_Subtitle_Style = Label::TextTips;
	const Navigation_TitleBg_Style = Quad::Bgs1;
	const Navigation_TitleBg_Substyle = Bgs1::BgTitlePage;
	/**#@-*/
	
	/**#@+
	 * Default styles for the page navigator 
	 */
	const PageNavigator_ArrowNone_Substyle = Icons64x64_1::StarGold;
	const PageNavigator_ArrowNext_Substyle = Icons64x64_1::ArrowNext;
	const PageNavigator_ArrowPrev_Substyle = Icons64x64_1::ArrowPrev;
	const PageNavigator_ArrowLast_Substyle = Icons64x64_1::ArrowLast;
	const PageNavigator_ArrowFirst_Substyle = Icons64x64_1::ArrowFirst;
	const PageNavigator_ArrowFastNext_Substyle = Icons64x64_1::ArrowFastNext;
	const PageNavigator_ArrowFastPrev_Substyle = Icons64x64_1::ArrowFastPrev;
	/**#@-*/
}

?>