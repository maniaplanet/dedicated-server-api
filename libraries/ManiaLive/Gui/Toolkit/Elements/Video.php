<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 1709 $:
 * @author      $Author: svn $:
 * @date        $Date: 2011-01-07 14:06:13 +0100 (ven., 07 janv. 2011) $:
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