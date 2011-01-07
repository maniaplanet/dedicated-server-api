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

namespace ManiaLive\PluginHandler;

use ManiaLive\Application\FatalException;

class Config extends \ManiaLive\Config\Configurable
{
	protected $plugins = array();
	
	public $load;
	
	function __set($name, $value)
	{
		$this->plugins[$name] = $value;
	}
	
	function __get($name)
	{
		if (!isset($this->plugins[$name])) $this->plugins[$name] = array();
		{
			return $this->plugins[$name];
		}
	}
	
	function validate()
	{
		$this->setDefault('load', array());
	}
}

?>