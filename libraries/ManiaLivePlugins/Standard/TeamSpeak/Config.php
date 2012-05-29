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

namespace ManiaLivePlugins\Standard\TeamSpeak;

/**
 * @method \ManiaLivePlugins\Standard\TeamSpeak\Config getInstance()
 */
class Config extends \ManiaLib\Utils\Singleton
{
	public $voiceHost;
	public $voicePort = '9987';
	public $voicePassword = '';
	public $queryHost = '127.0.0.1';
	public $queryPort = '10011';
	public $queryLogin = 'serveradmin';
	public $queryPassword = '';
	public $groupPermissions = array(
		'b_virtualserver_token_use' => 1,
		'i_channel_max_depth' => 0,
		'b_channel_info_view' => 1,
		'b_channel_create_temporary' => 1,
		'b_channel_create_with_topic' => 1,
		'b_channel_create_with_password' => 1,
		'b_channel_create_modify_with_codec_speex8' => 1,
		'b_channel_create_modify_with_codec_speex16' => 1,
		'i_channel_create_modify_with_codec_maxquality' => 7,
		'i_channel_create_modify_with_codec_latency_factor_min' => 1,
		'b_channel_create_with_maxclients' => 1,
		'b_channel_create_with_needed_talk_power' => 1,
		'b_channel_join_permanent' => 1,
		'b_channel_join_semi_permanent' => 1,
		'b_channel_join_temporary' => 1,
		'i_group_auto_update_type' => 15,
		'i_group_needed_modify_power' => 75,
		'i_client_max_clones_uid' => 0,
		'i_client_max_avatar_filesize' => 200000,
		'i_client_max_channel_subscriptions' => -1,
		'b_client_request_talker' => 1,
		'b_client_info_view' => 1,
		'i_client_needed_serverquery_view_power' => 75,
		'i_client_needed_kick_from_server_power' => 25,
		'i_client_needed_kick_from_channel_power' => 25,
		'i_client_needed_ban_power' => 25,
		'i_client_needed_move_power' => 25,
		'b_client_channel_textmessage_send' => 1,
		'i_ft_file_download_power' => 25,
		'i_ft_file_browse_power' => 25,
		'i_ft_quota_mb_download_per_client' => -1,
		'i_ft_quota_mb_upload_per_client' => -1
	);
	
	public $serverChannelPath = '';
	public $serverChannelName = '';
	public $commentators = array();
	
	function getConnectUrl($login)
	{
		$player = \ManiaLive\Data\Storage::getInstance()->getPlayerObject($login);
		$queryArgs = 'nickname='.rawurlencode(substr(\ManiaLib\Utils\Formatting::stripStyles($player->nickName), 0, 30));
		if( ($channel = Structures\Channel::GetDefault()) )
			$queryArgs .= '&channel='.rawurlencode($channel->getPath());
		$queryArgs .= '&token='.rawurlencode(Connection::getInstance()->getToken($login));
		if($this->voicePassword)
			$queryArgs .= '&password='.rawurlencode($this->voicePassword);
		
		return 'ts3server://'.$this->voiceHost.':'.$this->voicePort.'?'.$queryArgs;
	}
}

?>