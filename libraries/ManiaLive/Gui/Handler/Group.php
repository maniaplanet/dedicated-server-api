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

namespace ManiaLive\Gui\Handler;

use ManiaLive\Utilities\Console;

use ManiaLive\DedicatedApi\Connection;

/**
 * This class represents a group of players who will receive some manialinks to display
 */
class Group
{
	/**
	 * Contain the logins, or the Id of the recipient
	 * @var null|string
	 */
	public $recipients;

	/**
	 * @todo make this an array so that there can be several displayablegroups for one group.
	 * @var \ManiaLive\Gui\Handler\DisplayableGroup
	 */
	public $displayableGroup;

	function __construct()
	{
		$this->displayableGroup = new DisplayableGroup();
	}

	function send()
	{
		$connection = Connection::getInstance();
		$xml = $this->displayableGroup->getXml($this->recipients);
		$timeout = (int) $this->displayableGroup->timeout;
		$hideOnClick = (bool) $this->displayableGroup->hideOnClick;

		if(!count($this->recipients))
		{
			$connection->sendDisplayManialinkPage(
				null, $xml, $timeout, $hideOnClick, true);
		}
		else
		{
			$connection->sendDisplayManialinkPage(
				$this->recipients, $xml, $timeout, $hideOnClick, true);
		}
	}
}