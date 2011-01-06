<?php
/**
 * @copyright NADEO (c) 2010
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