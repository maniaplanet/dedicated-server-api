<?php

/**
 * @file
 * TeamSpeak 3 PHP Framework
 *
 * $Id: Event.php 9/26/2011 7:06:29 scp@orilla $
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

namespace ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Adapter\ServerQuery;

/**
 * @class TeamSpeak3_Adapter_ServerQuery_Event
 * @brief Provides methods to analyze and format a ServerQuery event.
 */
class Event implements \ArrayAccess
{
  /**
   * Stores the event type.
   *
   * @var \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\String
   */
  protected $type = null;

  /**
   * Stores the event data.
   *
   * @var array
   */
  protected $data = null;

  /**
   * Stores the event data as an unparsed string.
   *
   * @var \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\String
   */
  protected $mesg = null;

  /**
   * Creates a new TeamSpeak3_Adapter_ServerQuery_Event object.
   *
   * @param  \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\String $evt
   * @param  \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Node\Host     $con
   * @throws \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Adapter\Exception
   * @return Event
   */
  public function __construct(\ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\String $evt, \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Node\Host $con = null)
  {
    if(!$evt->startsWith(\ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\TeamSpeak3::EVENT))
    {
      throw new \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Adapter\Exception("invalid notification event format");
    }

    list($type, $data) = $evt->split(\ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\TeamSpeak3::SEPARATOR_CELL, 2);

    if(empty($data))
    {
      throw new \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Adapter\Exception("invalid notification event data");
    }

    $fake = new \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\String(\ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\TeamSpeak3::ERROR . \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\TeamSpeak3::SEPARATOR_CELL . "id" . \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\TeamSpeak3::SEPARATOR_PAIR . 0 . \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\TeamSpeak3::SEPARATOR_CELL . "msg" . \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\TeamSpeak3::SEPARATOR_PAIR . "ok");
    $repl = new Reply(array($data, $fake), $type);

    $this->type = $type->substr(strlen(\ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\TeamSpeak3::EVENT));
    $this->data = $repl->toList();
    $this->mesg = $data;

    \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\Signal::getInstance()->emit("notifyEvent", $this, $con);
    \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\Signal::getInstance()->emit("notify" . ucfirst($this->type), $this, $con);
  }

  /**
   * Returns the event type string.
   *
   * @return \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\String
   */
  public function getType()
  {
    return $this->type;
  }

  /**
   * Returns the event data array.
   *
   * @return array
   */
  public function getData()
  {
    return $this->data;
  }

  /**
   * Returns the event data as an unparsed string.
   *
   * @return \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Helper\String
   */
  public function getMessage()
  {
    return $this->mesg;
  }

  /**
   * @ignore
   */
  public function offsetExists($offset)
  {
    return array_key_exists($offset, $this->data) ? TRUE : FALSE;
  }

  /**
   * @ignore
   */
  public function offsetGet($offset)
  {
    if(!$this->offsetExists($offset))
    {
      throw new Exception("invalid parameter", 0x602);
    }

    return $this->data[$offset];
  }

  /**
   * @ignore
   */
  public function offsetSet($offset, $value)
  {
    throw new \ManiaLivePlugins\Standard\TeamSpeak\TeamSpeak3\Node\Exception("event '" . $this->getType() . "' is read only");
  }

  /**
   * @ignore
   */
  public function offsetUnset($offset)
  {
    unset($this->data[$offset]);
  }

  /**
   * @ignore
   */
  public function __get($offset)
  {
    return $this->offsetGet($offset);
  }

  /**
   * @ignore
   */
  public function __set($offset, $value)
  {
    $this->offsetSet($offset, $value);
  }
}
