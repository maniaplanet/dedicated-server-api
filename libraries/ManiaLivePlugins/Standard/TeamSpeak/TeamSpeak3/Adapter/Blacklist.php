<?php

/**
 * @file
 * TeamSpeak 3 PHP Framework
 *
 * $Id: Blacklist.php 9/26/2011 7:06:29 scp@orilla $
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
 * @class TeamSpeak3_Adapter_Blacklist
 * @brief Provides methods to check if an IP address is currently blacklisted.
 */
class Blacklist extends AbstractAdapter
{
  /**
   * The IPv4 address or FQDN of the TeamSpeak Systems update server.
   *
   * @var string
   */
  protected $default_host = "blacklist.teamspeak.com";

  /**
   * The UDP port number of the TeamSpeak Systems update server.
   *
   * @var integer
   */
  protected $default_port = 17385;

  /**
   * Stores an array containing the latest build numbers.
   *
   * @var array
   */
  protected $build_numbers = null;

  /**
   * Connects the TeamSpeak3_Transport_Abstract object and performs initial actions on the remote
   * server.
   *
   * @return void
   */
  public function syn()
  {
    if(!isset($this->options["host"]) || empty($this->options["host"])) $this->options["host"] = $this->default_host;
    if(!isset($this->options["port"]) || empty($this->options["port"])) $this->options["port"] = $this->default_port;

    $this->initTransport($this->options, '\ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Transport\UDP');
    $this->transport->setAdapter($this);

    \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\Profiler::init(spl_object_hash($this));

    ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\Signal::getInstance()->emit("blacklistConnected", $this);
  }

  /**
   * The TeamSpeak3_Adapter_Blacklist destructor.
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
   * Returns TRUE if a specified $host IP address is currently blacklisted.
   *
   * @param  string $host
   * @throws Blacklist\Exception
   * @return boolean
   */
  public function isBlacklisted($host)
  {
    if(ip2long($host) === FALSE)
    {
      $addr = gethostbyname($host);

      if($addr == $host)
      {
        throw new Blacklist\Exception("unable to resolve IPv4 address (" . $host . ")");
      }

      $host = $addr;
    }

    $this->getTransport()->send("ip4:" . $host);
    $repl = $this->getTransport()->read(1);
    $this->getTransport()->disconnect();

    if(!count($repl))
    {
      return FALSE;
    }

    return ($repl->toInt()) ? FALSE : TRUE;
  }
}
