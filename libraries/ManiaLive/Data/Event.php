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

namespace ManiaLive\Data;

class Event extends \ManiaLive\Event\Event
{
	const ON_PLAYER_NEW_BEST_TIME = 1;
	const ON_PLAYER_NEW_RANK = 2;
	const ON_PLAYER_NEW_BEST_SCORE = 3;
	const ON_PLAYER_CHANGE_SIDE = 4;
	
	protected $onWhat;
	protected $params;
	
	function __construct($source, $onWhat, $params = array())
	{
		parent::__construct($source);
		$this->onWhat = $onWhat;
		$this->params = $params;
	}
	
	function fireDo($listener)
	{
		$method = null;
		
		switch($this->onWhat)
		{
			case self::ON_PLAYER_NEW_BEST_TIME: $method = 'onPlayerNewBestTime'; break;
			case self::ON_PLAYER_NEW_RANK: $method = 'onPlayerNewRank'; break;
			case self::ON_PLAYER_NEW_BEST_SCORE: $method = 'onPlayerNewBestScore'; break;
			case self::ON_PLAYER_CHANGE_SIDE: $method = 'onPlayerChangeSide'; break;
		}
		
		if ($method != null)
			call_user_func_array(array($listener, $method), $this->params);
	}
}
?>