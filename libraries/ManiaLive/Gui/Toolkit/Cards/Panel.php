<?php
/**
 * @author Maxime Raoust
 * @copyright 2009-2010 NADEO 
 * @package ManiaMod
 */
namespace ManiaLive\Gui\Toolkit\Cards;

use ManiaLive\Gui\Toolkit\Manialink;

use ManiaLive\Gui\Toolkit\Elements\Element;

use ManiaLive\Gui\Toolkit\Elements as Elements;
use ManiaLive\Gui\Toolkit as Toolkit;
/**
 * Panel
 * Very useful! A quad with a title and a title background
 */
class Panel extends Elements\Quad
{
	/**
	 * Reference on the title object (Label)
	 */
	public $title;
	/**
	 * Reference on the title background object (Quad)
	 */
	public $titleBg;
	
	function __construct ($sx=20, $sy=20)
	{	
		$this->sizeX = $sx;
		$this->sizeY = $sy;

		$titleBgWidth = $sx - 2;
		$titleWidth = $sx - 4;
		
		$this->setStyle(Toolkit\DefaultStyles::Panel_Style);
		$this->setSubStyle(Toolkit\DefaultStyles::Panel_Substyle);
		
		$this->titleBg = new Elements\Quad ($titleBgWidth, 4);
		$this->titleBg->setStyle(Toolkit\DefaultStyles::Panel_TitleBg_Style);
		$this->titleBg->setSubStyle(Toolkit\DefaultStyles::Panel_TitleBg_Substyle);

		$this->title = new Elements\Label ($titleWidth);
		$this->title->setStyle(Toolkit\DefaultStyles::Panel_Title_Style);
		$this->title->setPositionY(-0.75);
	}
	
	function setSize($sizeX, $sizeY)
	{
		parent::setSize($sizeX, $sizeY);
		$this->titleBg->setSizeX($sizeX-2);
		$this->title->setSizeX($sizeX-4);
	}
	
	function setSizeX($x)
	{
		parent::setSizeX($x);
		$this->titleBg->setSizeX($x-2);
		$this->title->setSizeX($x-4);
	}
	
	protected function postFilter()
	{
		// Algin the title and its bg at the top center of the main quad		
		$arr = Toolkit\Tools::getAlignedPos ($this, "center", "top");
		$x = $arr["x"];
		$y = $arr["y"];
		$this->titleBg->setHalign("center");
		$this->title->setHalign("center");
		
		// Draw them
		Manialink::beginFrame($x, $y-1, $this->posZ+0.1);
			$this->titleBg->save();
			$this->title->save();
		Manialink::endFrame();
	}
}

?>