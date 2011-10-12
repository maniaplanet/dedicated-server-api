<?php
/**
 * ManiaLib - Lightweight PHP framework for Manialinks
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */
 
namespace ManiaLib\Filters\Nadeo;

abstract class Validation
{
	static function login($login, $length=25)
	{
		if($login && strlen($login) <= $length)
		{
			if(preg_match('/^[a-zA-Z0-9-_\.]{1,'.$length.'}$/', $login))
			{
				return;
			}
		}
		throw new \InvalidArgumentException('Invalid login "'.$login.'"');
	}
	
	static function nickname($nickname)
	{
		if($nickname && strlen($nickname) <= 75)
		{
			return;
		}
		throw new \InvalidArgumentException('Invalid nickname "'.$nickname.'"');
	}
	
	static function manialink($manialink)
	{
		if($manialink && strlen($manialink) <= 255)
		{
			return;
		}
		throw new \InvalidArgumentException('Invalid manialink short url "'.$manialink.'"');
	}

	static function environment($environment)
	{
		switch($environment)
		{
			case 'Merge':
			case 'Bay':
			case 'Coast':
			case 'Desert':
			case 'Island':
			case 'Stadium':
			case 'Snow':
			case 'Rally':
				break;

			default:
				throw new \InvalidArgumentException(sprintf('Invalid environment "%s"', $environment));
		}
	}

	static function path($path)
	{
		// FIXME Validate path
		if(!$path)
		{
			throw new \InvalidArgumentException(sprintf('Invalid path "%s"', $path));
		}
	}

	static function mapUID($mapUID)
	{
		// FIXME Validate map uid
		if(!$mapUID || strlen($mapUID) > 27)
		{
			throw new \InvalidArgumentException(sprintf('Invalid map UID "%s"', $mapUID));
		}
	}
}

?>