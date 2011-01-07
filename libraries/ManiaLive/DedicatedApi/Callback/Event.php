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

namespace ManiaLive\DedicatedApi\Callback;

class Event extends  \ManiaLive\Event\Event
{
	protected $method;
	protected $parameters;
	
	function __construct($source, $method, $parameters)
	{
		parent::__construct($source);
		$this->method = $method;
		$this->parameters = $parameters;
	}
	
	function fireDo($listener)
	{
		call_user_func_array(array($listener, 'on'.$this->method), $this->parameters);
	}
}

?>