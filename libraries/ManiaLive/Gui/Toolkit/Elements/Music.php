<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\Gui\Toolkit\Elements;

/**
 * Music
 */
class Music extends Element
{
	protected $xmlTagName = 'music';
	protected $halign = null;
	protected $valign = null;
	protected $posX = null;
	protected $posY = null;
	protected $posZ = null;
	protected $data;

	function __construct()
	{
	}

	/**
	 * Sets the data to play. If you don't specify the second parameter, it will
	 * look for the image in the path defined by the APP_DATA_DIR_URL constant
	 * @param string The image filename (or URL)
	 * @param string The URL that will be appended to the image. Use null if you
	 * want to specify an absolute URL as first parameter
	 */
	function setData($filename, $absoluteUrl = APP_DATA_DIR_URL)
	{
		if($absoluteUrl)
		{
			$this->data = $absoluteUrl . $filename;
		}
		else
		{
			$this->data = $filename;
		}
	}

	/**
	 * Returns the data URL
	 * @return string
	 */
	function getData()
	{
		return $this->data;
	}

	protected function postFilter()
	{
		if($this->data !== null)
		{
			$this->xml->setAttribute('data', $this->data);
		}
	}
}

?>