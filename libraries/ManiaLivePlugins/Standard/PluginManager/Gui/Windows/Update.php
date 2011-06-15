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

namespace ManiaLivePlugins\Standard\PluginManager\Gui\Windows;

use ManiaLive\PluginHandler\RepositoryEntry;
use ManiaLib\Gui\Elements\Bgs1;
use ManiaLive\Gui\Windowing\Controls\Panel;
use ManiaLive\Gui\Windowing\Controls\ButtonResizeable;
use ManiaLib\Gui\Elements\Button;
use ManiaLib\Gui\Layouts\Column;
use ManiaLive\Gui\Windowing\Controls\Frame;
use ManiaLib\Gui\Elements\Label;

class Update extends \ManiaLive\Gui\Windowing\Window
{
	/**
	 * @var \ManiaLive\PluginHandler\RepositoryEntry
	 */
	protected $repoEntry;
	
	protected $lblHeader;
	protected $panel;
	protected $btnContainer;
	protected $btnDownload;
	protected $btnInfo;
	protected $btnRepository;
	
	function initializeComponents()
	{	
		$this->panel = new Panel();
		$this->panel->main->setSubStyle(Bgs1::BgTitle2);
		$this->panel->setTitle('Update available!');
		$this->addComponent($this->panel);
		
		$this->lblHeader = new Label();
		$this->lblHeader->setPosition(2, 6);
		$this->lblHeader->enableAutonewline();
		$this->addComponent($this->lblHeader);
		
		$this->btnDownload = new ButtonResizeable();
		$this->btnDownload->setText('Download Package');
		
		$this->btnInfo = new ButtonResizeable();
		$this->btnInfo->setText('Show More Information on the Web');
		
		$this->btnRepository = new ButtonResizeable();
		$this->btnRepository->setText('Show Package on the Repository ManiaLink');
		
		$this->btnContainer = new Frame();
		$this->btnContainer->setPosX(2);
		$this->btnContainer->applyLayout(new Column(0, 0, Column::DIRECTION_UP));
		$this->btnContainer->addComponent($this->btnInfo);
		$this->btnContainer->addComponent($this->btnRepository);
		$this->btnContainer->addComponent($this->btnDownload);
		$this->addComponent($this->btnContainer);
	}
	
	function setRepositoryEntry(RepositoryEntry $entry)
	{
		$this->repoEntry = $entry;
		$text = 'The plugin repository on $h[http://manialink.manialive.com]manialive$h has a new update for this plugin ...' . "\n\n";
		$text .= '$<$o' .  $this->repoEntry->name . '$>' . "\n";
		$text .= 'Uploaded on ' . $this->repoEntry->dateCreated . ' by ' . $this->repoEntry->author . "\n";
		$text .= $this->repoEntry->description . "\n\n";
		$text .= 'Currently running plugins that will be affected by an update: $i';
		$text .= implode(', ', array_keys($entry->plugins));
		$this->lblHeader->setText($text);
		
		$this->btnDownload->setUrl($entry->urlDownload);
		$this->btnInfo->setUrl($entry->urlInfo);
		$this->btnRepository->setManialink('http://manialink.manialive.com/plugins/details/?id=' . $entry->id);
	}
	
	function onResize()
	{
		$this->panel->setSize($this->sizeX, $this->sizeY);
		$this->btnContainer->setPositionY($this->sizeY - 2);
		$this->lblHeader->setSizeX($this->sizeX - 4);
		$this->btnDownload->setSizeX($this->sizeX - 4);
		$this->btnInfo->setSizeX($this->sizeX - 4);
		$this->btnRepository->setSizeX($this->sizeX - 4);
	}
}

?>