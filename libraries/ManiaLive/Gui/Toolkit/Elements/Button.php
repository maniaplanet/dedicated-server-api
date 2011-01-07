<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 1709 $:
 * @author      $Author: svn $:
 * @date        $Date: 2011-01-07 14:06:13 +0100 (ven., 07 janv. 2011) $:
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