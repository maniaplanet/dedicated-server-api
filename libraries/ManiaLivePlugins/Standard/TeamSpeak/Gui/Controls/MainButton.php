<?php
/**
 * TeamSpeak Plugin - Connect to a TeamSpeak 3 server
 * Original work by refreshfr
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\Standard\TeamSpeak\Gui\Controls;

use ManiaLib\Gui\Elements\BgsPlayerCard;
use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\Quad;

/**
 * Description of MainButton
 */
class MainButton extends \ManiaLive\Gui\Control
{
	private $background;
	private $text;
	private $icon;
	
	function __construct()
	{
		$this->background = new HackedQuad();
		$this->background->setBgcolor('0008');
		$this->background->setBgcolorFocus('36b6');
		$this->addComponent($this->background);
		
		$this->text = new Label();
		$this->text->setTextSize(1);
		$this->text->setValign('center2');
		$this->text->setVisibility(false);
		$this->addComponent($this->text);
		
		$this->icon = new Quad();
		$this->icon->setAlign('right', 'center');
		$this->icon->setVisibility(false);
		$this->addComponent($this->icon);
	}
	
	function setBgcolor($bgcolor)
	{
		$this->background->setBgcolor($bgcolor);
	}
	
	function setBgcolorFocus($bgcolor)
	{
		$this->background->setBgcolorFocus($bgcolor);
	}
	
	function showText($show=true)
	{
		$this->text->setVisibility($show);
	}
	
	function setText($text)
	{
		$this->text->setText($text);
	}
	
	function enableAutonewline()
	{
		$this->text->enableAutonewline();
	}
	
	function showIcon($show=true)
	{
		$this->icon->setVisibility($show);
	}
	
	function setIconStyle($style, $substyle)
	{
		$this->icon->setStyle($style);
		$this->icon->setSubStyle($substyle);
	}
	
	function setIconImage($image)
	{
		$this->icon->setImage($image, true);
	}
	
	function setAction($action)
	{
		$this->background->setAction($action);
		$this->background->setUrl(null);
	}
	
	function setUrl($url)
	{
		$this->background->setAction(null);
		$this->background->setUrl($url);
	}
	
	function onResize($oldX, $oldY)
	{
		$this->background->setSize($this->sizeX, $this->sizeY);
		$this->text->setSize($this->sizeX - ($this->icon->isVisible() ? $this->sizeY : 2));
		$this->text->setPosition(1, -$this->sizeY / 2);
		$this->icon->setSize($this->sizeY - 1, $this->sizeY - 1);
		$this->icon->setPosition($this->sizeX - .5, -$this->sizeY / 2);
	}
}

?>