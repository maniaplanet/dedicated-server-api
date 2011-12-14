<?php

/**
 * @file
 * TeamSpeak 3 PHP Framework
 *
 * $Id: FileTransfer.php 9/26/2011 7:06:29 scp@orilla $
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

namespace ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Adapter;

/**
 * @class TeamSpeak3_Adapter_FileTransfer
 * @brief Provides low-level methods for file transfer communication with a TeamSpeak 3 Server.
 */
class FileTransfer extends AbstractAdapter
{
  /**
   * Connects the TeamSpeak3_Transport_Abstract object and performs initial actions on the remote
   * server.
   *
   * @throws TeamSpeak3_Adapter_Exception
   * @return void
   */
  public function syn()
  {
    $this->initTransport($this->options);
    $this->transport->setAdapter($this);

    \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\Profiler::init(spl_object_hash($this));

    \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\Signal::getInstance()->emit("filetransferConnected", $this);
  }

  /**
   * The TeamSpeak3_Adapter_FileTransfer destructor.
   *
   * @return void
   */
  public function __destruct()
  {
    if($this->getTransport() instanceof \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Transport\AbstractTransport && $this->getTransport()->isConnected())
    {
      $this->getTransport()->disconnect();
    }
  }

  /**
   * Sends a valid file transfer key to the server to initialize the file transfer.
   *
   * @param  string $ftkey
   * @throws FileTransfer\Exception
   * @return void
   */
  protected function init($ftkey)
  {
    if(strlen($ftkey) != 32)
    {
      throw new FileTransfer\Exception("invalid file transfer key format");
    }

    $this->getProfiler()->start();
    $this->getTransport()->send($ftkey);

    \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\Signal::getInstance()->emit("filetransferHandshake", $this);
  }

  /**
   * Sends the content of a file to the server.
   *
   * @param  string  $ftkey
   * @param  integer $seek
   * @param  string  $data
   * @throws FileTransfer\Exception
   * @return void
   */
  public function upload($ftkey, $seek, $data)
  {
    $this->init($ftkey);

    $size = strlen($data);
    $seek = intval($seek);
    $pack = 4096;

    \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\Signal::getInstance()->emit("filetransferUploadStarted", $ftkey, $seek, $size);

    for(;$seek < $size;)
    {
      $rest = $size-$seek;
      $pack = $rest < $pack ? $rest : $pack;
      $buff = substr($data, $seek, $pack);
      $seek = $seek+$pack;

      $this->getTransport()->send($buff);

      \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\Signal::getInstance()->emit("filetransferUploadProgress", $ftkey, $seek, $size);
    }

    $this->getProfiler()->stop();

    \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\Signal::getInstance()->emit("filetransferUploadFinished", $ftkey, $seek, $size);

    if($seek < $size)
    {
      throw new FileTransfer\Exception("incomplete file upload (" . $seek . " of " . $size . " bytes)");
    }
  }

  /**
   * Returns the content of a downloaded file as a \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\String object.
   *
   * @param  string  $ftkey
   * @param  integer $size
   * @param  boolean $passthru
   * @throws FileTransfer\Exception
   * @return \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\String
   */
  public function download($ftkey, $size, $passthru = FALSE)
  {
    $this->init($ftkey);

    if($passthru)
    {
      return $this->passthru($size);
    }

    $buff = new \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\String("");
    $size = intval($size);
    $pack = 4096;

    \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\Signal::getInstance()->emit("filetransferDownloadStarted", $ftkey, count($buff), $size);

    for($seek = 0;$seek < $size;)
    {
      $rest = $size-$seek;
      $pack = $rest < $pack ? $rest : $pack;
      $data = $this->getTransport()->read($rest < $pack ? $rest : $pack);
      $seek = $seek+$pack;

      $buff->append($data);

      \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\Signal::getInstance()->emit("filetransferDownloadProgress", $ftkey, count($buff), $size);
    }

    $this->getProfiler()->stop();

    \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\Signal::getInstance()->emit("filetransferDownloadFinished", $ftkey, count($buff), $size);

    if(strlen($buff) != $size)
    {
      throw new FileTransfer\Exception("incomplete file download (" . count($buff) . " of " . $size . " bytes)");
    }

    return $buff;
  }

  /**
   * Outputs all remaining data on a TeamSpeak 3 file transfer stream using PHP's fpassthru()
   * function.
   *
   * @param  integer $size
   * @throws FileTransfer\Exception
   * @return void
   */
  protected function passthru($size)
  {
    $buff_size = fpassthru($this->getTransport()->getStream());

    if($buff_size != $size)
    {
      throw new FileTransfer\Exception("incomplete file download (" . intval($buff_size) . " of " . $size . " bytes)");
    }
  }
}
