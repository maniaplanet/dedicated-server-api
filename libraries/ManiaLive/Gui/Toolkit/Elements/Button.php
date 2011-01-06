<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\Gui\Toolkit\Elements;

use ManiaLive\Gui\Toolkit\DefaultStyles;

/**
 * Button
 */
class Button extends Label
{
	const CardButttonMedium       = 'CardButtonMedium';
	const CardButttonMediumWide   = 'CardButtonMediumWide';
	const CardButtonSmallWide     = 'CardButtonSmallWide';
	const CardButtonSmall         = 'CardButtonSmall';

	protected $subStyle = null;
	protected $style = DefaultStyles::Button_Style;

	function __construct($sizeX = 25, $sizeY = 3)
	{
		parent::__construct($sizeX, $sizeY);
	}
}

?>