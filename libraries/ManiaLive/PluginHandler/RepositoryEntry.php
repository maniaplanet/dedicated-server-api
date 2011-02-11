<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLive\PluginHandler;

class RepositoryEntry
{
	public $id;
	public $name;
	public $author;
	public $plugins = array();
	public $urlDownload;
	public $urlInfo;
	public $description;
	public $version;
	public $dateCreated;
	public $category;
	
	/**
	 * @return \ManiaLive\PluginHandler\RepositoryEntry
	 */
	static function fromResponse($response)
	{
		$me = new static();
		$me->id = $response->id;
		$me->author = $response->author;
		$me->name = $response->name;
		$me->description = $response->description;
		$me->urlDownload = $response->address;
		$me->urlInfo = $response->addressMore;
		$me->version = floatval($response->version);
		$me->dateCreated = $response->dateCreated;
		$me->category = $response->category;
		$me->plugins = array();
		return $me;
	}
}

?>