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

namespace ManiaLive\Gui\Handler;

use ManiaLib\Gui\Manialink;

/**
 * Represents the root elements for manialink element send to
 * the dedicated server
 * It contents manialink and Custom Ui data
 */
abstract class Manialinks
{
	public static $domDocument;
	public static $parentNodes;

	final public static function load()
	{
		self::$domDocument = new \DOMDocument;
		self::$parentNodes = array();

		$manialink = self::$domDocument->createElement('manialinks');
		self::$domDocument->appendChild($manialink);
		self::$parentNodes[] = $manialink;
	}

	final public static function getXml()
	{
		return self::$domDocument->saveXML(self::$domDocument);
	}

	final public static function beginManialink($x=0, $y=0, $z=0, $id=null, $version=1)
	{
		// Create DOM element
		$manialink = self::$domDocument->createElement('manialink');
		
		if ($x)
		{
			$manialink->setAttribute('posx', $x);
		}
		if ($y)
		{
			$manialink->setAttribute('posy', $y);
		}
		if ($z)
		{
			$manialink->setAttribute('posz', $z);
		}
		
		if($id)
		{
			$manialink->setAttribute('id', $id);
		}
		
		if($version)
		{
			$manialink->setAttribute('version', $version);
		}
		end(self::$parentNodes)->appendChild($manialink);

		// Update stacks
		self::$parentNodes[] = $manialink;

		// Update Manialink class
		Manialink::$domDocument = self::$domDocument;
		Manialink::$parentNodes = self::$parentNodes;
		Manialink::$parentLayouts = array();
	}

	final public static function endManialink()
	{
		if(!end(self::$parentNodes)->hasChildNodes())
		{
			end(self::$parentNodes)->nodeValue = ' ';
		}
		array_pop(self::$parentNodes);

		Manialink::$domDocument = null;
		Manialink::$parentNodes = null;
		Manialink::$parentLayouts = null;
	}

	final public static function beginCustomUi()
	{
		$customUi = self::$domDocument->createElement('custom_ui');
		end(self::$parentNodes)->appendChild($customUi);
		self::$parentNodes[] = $customUi;
	}

	final public static function endCustomUi()
	{
		array_pop(self::$parentNodes);
	}
	
	final protected static function setVisibility($parameter, $visibility)
	{
		$parameterNode = self::$domDocument->createElement($parameter);
		$parameterVisibility = self::$domDocument->createAttribute('visible');
		$parameterNode->appendChild($parameterVisibility);

		if($visibility)
		{
			$parameterVisibility->appendChild(self::$domDocument->createTextNode('true'));
		}
		else
		{
			$parameterVisibility->appendChild(self::$domDocument->createTextNode('false'));
		}

		end(self::$parentNodes)->appendChild($parameterNode);
	}

	final public static function setNoticeVisibility($visibility = true)
	{
		self::setVisibility('notice', $visibility);
	}

	final public static function setChallengeInfoVisibility($visibility = true)
	{
		self::setVisibility('challenge_info', $visibility);
	}

	final public static function setChatVisibility($visibility = true)
	{
		self::setVisibility('chat', $visibility);
	}

	final public static function setCheckpointListVisibility($visibility = true)
	{
		self::setVisibility('checkpoint_list', $visibility);
	}

	final public static function setRoundScoresVisibility($visibility = true)
	{
		self::setVisibility('round_scores', $visibility);
	}

	final public static function setScoretableVisibility($visibility = true)
	{
		self::setVisibility('scoretable', $visibility);
	}

	final public static function setGlobalVisibility($visibility = true)
	{
		self::setVisibility('global', $visibility);
	}

	static function appendXML($XML)
	{
		$doc = new DOMDocument();
		$doc->loadXML($XML);
		$node = self::$domDocument->importNode($doc->firstChild, true);
		end(self::$parentNodes)->appendChild($node);
	}
}
?>