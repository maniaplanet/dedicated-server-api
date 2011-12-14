<?php

/**
 * @file
 * TeamSpeak 3 PHP Framework
 *
 * $Id: Interface.php 9/26/2011 7:06:29 scp@orilla $
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   TeamSpeak3
 * @version   1.1.8-beta
 * @author    Sven 'ScP' Paulsen
 * @copyright Copyright (c) 2010 by Planet TeamSpeak. All rights reserved.
 */

namespace ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\Signal;

/**
 * @class TeamSpeak3_Helper_Signal_Interface
 * @brief Interface class describing the layout for TeamSpeak3_Helper_Signal callbacks.
 */
interface SignalInterface
{
  /**
   * Possible callback for '<adapter>Connected' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("serverqueryConnected", array($object, "onConnect"));
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("filetransferConnected", array($object, "onConnect"));
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("blacklistConnected", array($object, "onConnect"));
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("updateConnected", array($object, "onConnect"));
   *
   * @param  \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Adapter\AbstractAdapter $adapter
   * @return void
   */
  public function onConnect(\ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Adapter\AbstractAdapter $adapter);

  /**
   * Possible callback for '<adapter>Disconnected' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("serverqueryDisconnected", array($object, "onDisconnect"));
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("filetransferDisconnected", array($object, "onDisconnect"));
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("blacklistDisconnected", array($object, "onDisconnect"));
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("updateDisconnected", array($object, "onDisconnect"));
   *
   * @return void
   */
  public function onDisconnect();

  /**
   * Possible callback for 'serverqueryCommandStarted' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("serverqueryCommandStarted", array($object, "onCommandStarted"));
   *
   * @param  string $cmd
   * @return void
   */
  public function onCommandStarted($cmd);

  /**
   * Possible callback for 'serverqueryCommandFinished' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("serverqueryCommandFinished", array($object, "onCommandFinished"));
   *
   * @param  string $cmd
   * @param  \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Adapter\ServerQuery\Reply $reply
   * @return void
   */
  public function onCommandFinished($cmd, \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Adapter\ServerQuery\Reply $reply);

  /**
   * Possible callback for 'notifyEvent' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("notifyEvent", array($object, "onEvent"));
   *
   * @param  \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Adapter\ServerQuery\Event $event
   * @param  \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Node\Host $host
   * @return void
   */
  public function onEvent(\ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Adapter\ServerQuery\Event $event, \ManiaLivePlugins\Standard\TeamSpeak\Node\Host $host);

  /**
   * Possible callback for 'notifyError' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("notifyError", array($object, "onError"));
   *
   * @param  \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Adapter\ServerQuery\Reply $reply
   * @return void
   */
  public function onError(\ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Adapter\ServerQuery\Reply $reply);

  /**
   * Possible callback for 'notifyServerselected' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("notifyServerselected", array($object, "onServerselected"));
   *
   * @param  \ManiaLivePlugins\Standard\TeamSpeak\Node\Host $host
   * @return void
   */
  public function onServerselected(\ManiaLivePlugins\Standard\TeamSpeak\Node\Host $host);

  /**
   * Possible callback for 'notifyServercreated' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("notifyServercreated", array($object, "onServercreated"));
   *
   * @param  \ManiaLivePlugins\Standard\TeamSpeak\Node\Host $host
   * @param  integer $sid
   * @return void
   */
  public function onServercreated(\ManiaLivePlugins\Standard\TeamSpeak\Node\Host $host, $sid);

  /**
   * Possible callback for 'notifyServerdeleted' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("notifyServerdeleted", array($object, "onServerdeleted"));
   *
   * @param  \ManiaLivePlugins\Standard\TeamSpeak\Node\Host $host
   * @param  integer $sid
   * @return void
   */
  public function onServerdeleted(\ManiaLivePlugins\Standard\TeamSpeak\Node\Host $host, $sid);

  /**
   * Possible callback for 'notifyServerstarted' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("notifyServerstarted", array($object, "onServerstarted"));
   *
   * @param  \ManiaLivePlugins\Standard\TeamSpeak\Node\Host $host
   * @param  integer $sid
   * @return void
   */
  public function onServerstarted(\ManiaLivePlugins\Standard\TeamSpeak\Node\Host $host, $sid);

  /**
   * Possible callback for 'notifyServerstopped' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("notifyServerstopped", array($object, "onServerstopped"));
   *
   * @param  \ManiaLivePlugins\Standard\TeamSpeak\Node\Host $host
   * @param  integer $sid
   * @return void
   */
  public function onServerstopped(\ManiaLivePlugins\Standard\TeamSpeak\Node\Host $host, $sid);

  /**
   * Possible callback for 'notifyServershutdown' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("notifyServershutdown", array($object, "onServershutdown"));
   *
   * @param  \ManiaLivePlugins\Standard\TeamSpeak\Node\Host $host
   * @return void
   */
  public function onServershutdown(\ManiaLivePlugins\Standard\TeamSpeak\Node\Host $host);

  /**
   * Possible callback for 'notifyLogin' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("notifyLogin", array($object, "onLogin"));
   *
   * @param  \ManiaLivePlugins\Standard\TeamSpeak\Node\Host $host
   * @return void
   */
  public function onLogin(\ManiaLivePlugins\Standard\TeamSpeak\Node\Host $host);

  /**
   * Possible callback for 'notifyLogout' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("notifyLogout", array($object, "onLogout"));
   *
   * @param  \ManiaLivePlugins\Standard\TeamSpeak\Node\Host $host
   * @return void
   */
  public function onLogout(\ManiaLivePlugins\Standard\TeamSpeak\Node\Host $host);

  /**
   * Possible callback for 'notifyTokencreated' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("notifyTokencreated", array($object, "onTokencreated"));
   *
   * @param  \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Node\Server $server
   * @param  string $token
   * @return void
   */
  public function onTokencreated(\ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Node\Server $server, $token);

  /**
   * Possible callback for 'filetransferHandshake' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("filetransferHandshake", array($object, "onFtHandshake"));
   *
   * @param  \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Adapter\FileTransfer $adapter
   * @return void
   */
  public function onFtHandshake(\ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Adapter\FileTransfer $adapter);

  /**
   * Possible callback for 'filetransferUploadStarted' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("filetransferUploadStarted", array($object, "onFtUploadStarted"));
   *
   * @param  string  $ftkey
   * @param  integer $seek
   * @param  integer $size
   * @return void
   */
  public function onFtUploadStarted($ftkey, $seek, $size);

  /**
   * Possible callback for 'filetransferUploadProgress' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("filetransferUploadProgress", array($object, "onFtUploadProgress"));
   *
   * @param  string  $ftkey
   * @param  integer $seek
   * @param  integer $size
   * @return void
   */
  public function onFtUploadProgress($ftkey, $seek, $size);

  /**
   * Possible callback for 'filetransferUploadFinished' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("filetransferUploadFinished", array($object, "onFtUploadFinished"));
   *
   * @param  string  $ftkey
   * @param  integer $seek
   * @param  integer $size
   * @return void
   */
  public function onFtUploadFinished($ftkey, $seek, $size);

  /**
   * Possible callback for 'filetransferDownloadStarted' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("filetransferDownloadStarted", array($object, "onFtDownloadStarted"));
   *
   * @param  string  $ftkey
   * @param  integer $buff
   * @param  integer $size
   * @return void
   */
  public function onFtDownloadStarted($ftkey, $buff, $size);

  /**
   * Possible callback for 'filetransferDownloadProgress' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("filetransferDownloadProgress", array($object, "onFtDownloadProgress"));
   *
   * @param  string  $ftkey
   * @param  integer $buff
   * @param  integer $size
   * @return void
   */
  public function onFtDownloadProgress($ftkey, $buff, $size);

  /**
   * Possible callback for 'filetransferDownloadFinished' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("filetransferDownloadFinished", array($object, "onFtDownloadFinished"));
   *
   * @param  string  $ftkey
   * @param  integer $buff
   * @param  integer $size
   * @return void
   */
  public function onFtDownloadFinished($ftkey, $buff, $size);

  /**
   * Possible callback for '<adapter>DataRead' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("serverqueryDataRead", array($object, "onDebugDataRead"));
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("filetransferDataRead", array($object, "onDebugDataRead"));
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("blacklistDataRead", array($object, "onDebugDataRead"));
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("updateDataRead", array($object, "onDebugDataRead"));
   *
   * @param  string $data
   * @return void
   */
  public function onDebugDataRead($data);

  /**
   * Possible callback for '<adapter>DataSend' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("serverqueryDataSend", array($object, "onDebugDataSend"));
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("filetransferDataSend", array($object, "onDebugDataSend"));
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("blacklistDataSend", array($object, "onDebugDataSend"));
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("updateDataSend", array($object, "onDebugDataSend"));
   *
   * @param  string $data
   * @return void
   */
  public function onDebugDataSend($data);

  /**
   * Possible callback for '<adapter>WaitTimeout' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("serverqueryWaitTimeout", array($object, "onWaitTimeout"));
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("filetransferWaitTimeout", array($object, "onWaitTimeout"));
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("blacklistWaitTimeout", array($object, "onWaitTimeout"));
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("updateWaitTimeout", array($object, "onWaitTimeout"));
   *
   * @param  integer $time
   * @param  \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Adapter\AbstractAdapter $adapter
   * @return void
   */
  public function onWaitTimeout($time, \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Adapter\AbstractAdapter $adapter);

  /**
   * Possible callback for 'errorException' signals.
   *
   * === Examples ===
   *   - TeamSpeak3_Helper_Signal::getInstance()->subscribe("errorException", array($object, "onException"));
   *
   * @param  ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Exception $e
   * @return void
   */
  public function onException(ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Exception $e);
}
