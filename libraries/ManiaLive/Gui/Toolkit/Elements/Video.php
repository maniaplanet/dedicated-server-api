<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\Gui\Toolkit\Elements;

/**
 * Video
 */
class Video extends Audio
{
	protected $xmlTagName = 'video';

	function __construct($sx = 32, $sy = 24)
	{
		$this->sizeX = $sx;
		$this->sizeY = $sy;
	}
}

?>