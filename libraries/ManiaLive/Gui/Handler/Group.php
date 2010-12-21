<?php

namespace ManiaLive\Gui\Handler;

use ManiaLive\Utilities\Console;

use ManiaLive\DedicatedApi\Connection;

/**
 *
 * This class represents a group of players who will receive some manialinks to display
 * @author Philippe Melot
 * @copyright NADEO (c) 2010
 * 
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