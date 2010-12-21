<?php

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
		$this->xml->setAttribute('name', $this->name);
		if($this->defaultValue !== null)
		$this->xml->setAttribute('default', $this->defaultValue);
	}
}

?>