<?php
/**
 * TeamSpeak Plugin - Connect to a TeamSpeak 3 server
 * Original work by refreshfr
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\Standard\TeamSpeak\Tasks;

/**
 * Description of ChannelToggleComment
 */
class ChannelToggleComment extends AbstractTask
{
	private $channelId;
	private $enable;
	
	function __construct($config, $channelId, $enable)
	{
		parent::__construct($config);
		$this->channelId = $channelId;
		$this->enable = $enable;
	}
	
	protected function doRun()
	{
		$talkPower = $this->enable ? \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak::COMMENTATOR : 0;
		$this->connection()->request(sprintf('channeledit cid=%d channel_needed_talk_power=%d', $this->channelId, $talkPower));
	}
}

?>