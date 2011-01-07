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

namespace ManiaLive\Gui\Toolkit\Elements;

/**
 * FileEntry
 * File input field for Manialinks
 */
class FileEntry extends Entry
{
	protected $xmlTagName = 'fileentry';
	protected $folder;

	/**
	 * Sets the default folder
	 * @param string
	 */
	function setFolder($folder)
	{
		$this->folder = $folder;
	}

	/**
	 * Returns the default folder
	 * @return string
	 */
	function getFolder()
	{
		return $this->folder;
	}

	protected function postFilter()
	{
		parent::postFilter();
		if($this->folder !== null)
		{
			$this->xml->setAttribute('folder', $this->folder);
		}
	}
}

?>