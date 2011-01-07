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
 * Audio player
 */
class Audio extends Music
{
	protected $xmlTagName = 'music';
	protected $posX = 0;
	protected $posY = 0;
	protected $posZ = 0;
	protected $play;
	protected $looping = 0;

	/**
	 * Autoplay the data when it's done loading
	 */
	function autoPlay()
	{
		$this->play = 1;
	}

	/**
	 * Loop when the end of the data is reached
	 */
	function enableLooping()
	{
		$this->looping = 1;
	}

	/**
	 * Returns whether auto playing is enabled
	 * @return boolean
	 */
	function getAutoPlay()
	{
		return $this->play;
	}

	/**
	 * Returns whether looping is enabled
	 * @return boolean
	 */
	function getLooping()
	{
		return $this->looping;
	}

	protected function postFilter()
	{
		parent::postFilter();
		if($this->play !== null)
		{
			$this->xml->setAttribute('play', $this->play);
		}
		if($this->looping !== null)
		{
			$this->xml->setAttribute('looping', $this->looping);
		}
	}
}

?>