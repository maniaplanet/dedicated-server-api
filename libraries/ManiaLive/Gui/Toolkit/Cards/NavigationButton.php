<?php
/**
 * @author Maxime Raoust
 * @copyright 2009-2010 NADEO 
 */

namespace ManiaLive\Gui\Toolkit\Cards;

use ManiaLive\Gui\Toolkit as Toolkit;

use ManiaLive\Gui\Toolkit\Elements as Elements;

/**
 * Navigation button
 * For the Navigation card
 * @see Navigation
 */ 
class NavigationButton extends Elements\Quad
{
	public $text;
	public $icon;
	
	protected $forceLinks = true;
	public $iconSizeMinimizer = 1.5;
	public $textSizeMinimizer = 3;
	public $textOffset = 9;
	public $isSelected = false;

	function __construct ($sx=29.5, $sy=8.5) 
	{
		$this->sizeX = $sx;
		$this->sizeY = $sy;	
		
		$this->setStyle(Toolkit\DefaultStyles::NavigationButton_Style);
		$this->setSubStyle(Toolkit\DefaultStyles::NavigationButton_Substyle);
		
		$this->text = new Elements\Label();
		$this->text->setValign("center");
		$this->text->setPosition($this->textOffset, 0.25, 1);
		$this->text->setStyle(Toolkit\DefaultStyles::NavigationButton_Text_Style);
		
		$this->icon = new Elements\Icons128x128_1($this->sizeY-$this->iconSizeMinimizer);
		$this->icon->setValign("center");
		$this->icon->setPosition(1, 0, 1);
		
	}
	
	/**
	 * Sets the button selected and change its styles accordingly
	 */
	function setSelected() 
	{
		$this->setSubStyle(Toolkit\DefaultStyles::NavigationButton_Selected_Substyle);
		$this->text->setStyle(Toolkit\DefaultStyles::NavigationButton_Selected_Text_Style);
		$this->isSelected = true;	
	}
	
	protected function postFilter ()
	{		
		if($this->isSelected)
		{
			$this->text->setText('$0cf'.$this->text->getText());
		}
		
		$this->text->setSizeX($this->sizeX - $this->text->getPosX() - $this->textSizeMinimizer);
		$this->text->setSizeY(0);
		$this->icon->setSize($this->sizeY-$this->iconSizeMinimizer, $this->sizeY-$this->iconSizeMinimizer);
		
		if($this->forceLinks)
		{
			$this->text->addLink($this);
			$this->icon->addLink($this);
		}
		$newPos = Toolkit\Tools::getAlignedPos ($this, "left", "center");
		
		// Drawing
		Toolkit\Manialink::beginFrame($newPos["x"], $newPos["y"], $this->posZ+1);
			$this->text->save();
			$this->icon->save();
		Toolkit\Manialink::endFrame();
	}
}

?>