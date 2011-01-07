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

use ManiaLive\Gui\Toolkit\DefaultStyles;

/**
 * Entry
 * Input field for Manialinks
 */
class Entry extends Label
{
	protected $xmlTagName = 'entry';
	protected $style = DefaultStyles::Entry_Style;
	protected $name;
	protected $defaultValue;

	/**
	 * Sets the name of the entry. Will be used as the parameter name in the URL
	 * when submitting the page
	 * @param string
	 */
	function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * Sets the default value of the entry
	 * @param mixed
	 */
	function setDefault($value)
	{
		$this->defaultValue = $value;
	}

	/**
	 * Returns the name of the entry
	 * @return string
	 */
	function getName()
	{
		return $this->name;
	}

	/**
	 * Returns the default value of the entry
	 * @return mixed
	 */
	function getDefault()
	{
		return $this->defaultValue;
	}

	protected function postFilter()
	{
		parent::postFilter();
		if($this->name !== null)
		{
			$this->xml->setAttribute('name', $this->name);
		}
		if($this->defaultValue !== null)
		{
			$this->xml->setAttribute('default', $this->defaultValue);
		}
	}
}

?>