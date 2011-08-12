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

namespace ManiaLib\Gui\Elements;

/**
 * Music
 */
class Music extends \ManiaLib\Gui\Element
{
	/**#@+
	 * @ignore 
	 */
	protected $xmlTagName = 'music';
	protected $halign = null;
	protected $valign = null;
	protected $posX = null;
	protected $posY = null;
	protected $posZ = null;
	protected $data;
	protected $dataId;
	/**#@-*/
	
	function __construct()
	{
	}
	
	/**
	 * Sets the data to play
	 * @param string The data filename (or URL)
	 * @param bool Whether to prefix the filename with the default media dir URL 
	 */
	function setData($filename, $absoluteUrl = false)
	{
		if(!$absoluteUrl)
		{
			$this->data = \ManiaLib\Gui\Manialink::$mediaURL.$filename;
		}
		else
		{
			$this->data = $filename;
		}
	}
	
	/**
	 * Sets the data id to play
	 * @param string The data id
	 */
	function setDataId($dataId)
	{
		$this->dataId = $dataId;
	}
	
	/**
	 * Returns the data URL
	 * @return string
	 */
	function getData()
	{
		return $this->data;
	}
	
	/**
	 * Returns the data id
	 * @return string
	 */
	function getDataId()
	{
		return $this->dataId;
	}

	/**
	 * @ignore 
	 */
	protected function postFilter()
	{
		if($this->data !== null)
			$this->xml->setAttribute('data', $this->data);
		if($this->dataId !== null)
			$this->xml->setAttribute('dataId', $this->dataId);
	}
}

?>