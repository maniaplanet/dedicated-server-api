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
 * Description of ChannelCreate
 */
class ChannelCreate extends AbstractTask
{
	private $name;
	private $parentId;
	private $talkPower;
	private $joinPower;
	
	function __construct($config, $name, $parentId=0, $talkPower=0, $joinPower=0)
	{
		parent::__construct($config);
		$this->name = $name;
		$this->parentId = $parentId;
		$this->talkPower = $talkPower;
		$this->joinPower = $joinPower;
	}
	
	protected function doRun()
	{
		$channelProperties = array(
			'channel_name' => $this->name,
			'channel_description' => 'Created by ManiaLive TS Plugin',
			'channel_flag_permanent' => false,
			'channel_flag_semi_permanent' => true,
		);
		if($this->parentId)
		{
			$channelProperties['channel_codec'] = \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\TeamSpeak3::CODEC_SPEEX_NARROWBAND;
			$channelProperties['cpid'] = $this->parentId;
		}
		else
			$channelProperties['channel_codec'] = \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\TeamSpeak3::CODEC_SPEEX_WIDEBAND;
		if($this->talkPower)
			$channelProperties['channel_needed_talk_power'] = $this->talkPower;

		$channelId = $this->connection()->channelCreate($channelProperties);
		
		if($this->joinPower)
			$this->connection()->channelPermAssign($channelId, 'i_channel_needed_join_power', $this->joinPower);
		
		return $channelId;
	}
}

?>