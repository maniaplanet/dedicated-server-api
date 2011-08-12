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

namespace ManiaLib\Gui\Cards\Navigation;

/**
 * Navigation button
 */ 
class Button extends \ManiaLib\Gui\Elements\Quad
{
	/**
	 * TrackMania formatting string appended to the text when a button
	 * is selected (default is just a light blue color)
	 */
	static public $selectedTextStyle = '$0cf';
	
	/**
	 * @var \ManiaLib\Gui\Elements\Label
	 */
	public $text;
	/**
	 * @var \ManiaLib\Gui\Elements\Icon
	 */
	public $icon;
	public $iconSizeMinimizer = 1.5;
	public $textSizeMinimizer = 3;
	public $textOffset = 9;
	/**
	 * @ignore
	 */
	protected $isSelected = false;
	protected $forceLinks = true;

	function __construct ($sx=29.5, $sy=8.5) 
	{
		$this->sizeX = $sx;
		$this->sizeY = $sy;	
		
		$this->setStyle(\ManiaLib\Gui\DefaultStyles::NavigationButton_Style);
		$this->setSubStyle(\ManiaLib\Gui\DefaultStyles::NavigationButton_Substyle);
		
		$this->text = new \ManiaLib\Gui\Elements\Label();
		$this->text->setValign("center");
		$this->text->setPosition($this->textOffset, 0.25, 1);
		$this->text->setStyle(\ManiaLib\Gui\DefaultStyles::NavigationButton_Text_Style);
		
		$this->icon = new \ManiaLib\Gui\Elements\Icons128x128_1($this->sizeY-$this->iconSizeMinimizer);
		$this->icon->setValign("center");
		$this->icon->setPosition(1, 0, 1);
		
	}
	
	/**
	 * Sets the button selected and change its styles accordingly
	 */
	function setSelected() 
	{
		$this->setSubStyle(\ManiaLib\Gui\DefaultStyles::NavigationButton_Selected_Substyle);
		$this->isSelected = true;	
	}
	
	/**
	 * @ignore
	 */
	protected function postFilter ()
	{		
		if($this->isSelected)
		{	
			if($this->text->getText())
			{
				$this->text->setText(self::$selectedTextStyle.$this->text->getText());
			}
		}
		
		$this->text->setSizeX($this->sizeX - $this->text->getPosX() - $this->textSizeMinimizer);
		$this->text->setSizeY(0);
		$this->icon->setSize($this->sizeY-$this->iconSizeMinimizer, $this->sizeY-$this->iconSizeMinimizer);
		
		if($this->forceLinks)
		{
			$this->text->addLink($this);
			$this->icon->addLink($this);
		}
		$newPos = \ManiaLib\Gui\Tools::getAlignedPos ($this, "left", "center");
		
		// Drawing
		\ManiaLib\Gui\Manialink::beginFrame($newPos["x"], $newPos["y"], $this->posZ+1);
			$this->text->save();
			$this->icon->save();
		\ManiaLib\Gui\Manialink::endFrame();
	}
}

?>